# BUSINESS RULES

## 1. State machine
```
Post:        open → matched → closed
               ├→ cancelled (chủ bài hủy; hoặc người được chọn hủy SAU cutoff)
               ├→ open      (người được chọn hủy TRƯỚC join_cutoff_at)
               └→ expired   (đến expires_at, chưa chọn)
JoinRequest: pending → accepted → cancelled
                 └→ declined | withdrawn | expired
Chat:        (chưa tồn tại) → active → read_only → messages bị xóa
```
`matched` = đã chọn người; `closed` = đã qua suất chiếu, hoàn tất vòng đời.

## 2. Điều kiện tham gia
- Chỉ tài khoản có `photo_verified_at` **và** `age_verified_at` mới đăng bài hoặc join.
- Bài `women_only`: chỉ nữ đã verify được **tạo** (403 ở FormRequest) và **xem/join**.
- Mỗi người join **một lần** mỗi bài (unique index).
- Tối đa **10 join pending**/người; có giới hạn join/ngày (rate limit).
- Mỗi bài nhận tối đa `max_join_requests` join pending (`posts.join_count`, trả **409** khi đầy).
- Không join bài của người đã block mình hoặc mình đã block (**hai chiều**, ở feed lẫn danh sách join).
- Không join/chọn khi trùng khoảng `[start, end)` với bài đã accepted hoặc bài mình là host đã matched.
- `join_cutoff_at = start_time − 2h` (quyết định sản phẩm, có thể đổi): sau mốc này **không join, không chọn** được.
- Người join rút được khi còn `pending`; chủ bài hủy được trước giờ chiếu.
- Feed chỉ hiện **số** người đã xin tham gia, không lộ danh sách/avatar.
- Feed chỉ hiện: tên, ảnh đã verify, phim, rạp, giờ; **không** vị trí chính xác.
- Trước khi join, người join thấy rating, số lượt đánh giá, huy hiệu verify của chủ bài (đối xứng).

## 3. Chọn người — thao tác nguyên tử 5 lớp
Endpoint: `POST /api/posts/{post}/select/{join}` (chủ bài).
1. **Update có điều kiện trên post:** `where _id=:post AND status='open' AND host_id=:me AND now < join_cutoff_at AND now < expires_at` → `status='matched'`, `matched_at=now`, `join_count=0`. `modifiedCount == 0` ⇒ 409.
2. **Transaction** bao quanh toàn bộ.
3. **Unique index** `chats.join_request_id` và `chats.post_id`: dù hai request chạy đua, chat thứ hai bị chặn.
4. **Accept join có điều kiện** `status='pending'`, kiểm tra `modifiedCount`; các join pending còn lại của post → `declined`.
5. **Kiểm tra lại trong transaction:** block hai chiều, trạng thái tài khoản hai bên, verify, chồng lấn thời gian.

Sau đó: tạo chat `active` (`read_only_at = end_time+24h`, `expire_at = end_time+3d`), tin `system` `matched`, `stats.accepted_count++` cho người được chọn, gửi push.
**Không có replica set:** bỏ transaction, giữ lớp 1, 3, 4 và hoàn tác thủ công khi lỗi giữa chừng.

```php
// Khung tham khảo (SelectJoinService)
return DB::connection('mongodb')->transaction(function () use ($post, $join, $me) {
    $ok = Post::where('_id', $post->id)->where('status', 'open')->where('host_id', $me->id)
        ->where('join_cutoff_at', '>', now())->update(['status' => 'matched', 'matched_at' => now(), 'join_count' => 0]);
    if (!$ok) abort(409, 'post_not_open');
    $acc = JoinRequest::where('_id', $join->id)->where('post_id', $post->id)
        ->where('status', 'pending')->update(['status' => 'accepted', 'accepted_at' => now()]);
    if (!$acc) abort(409, 'join_not_pending');
    // lớp 5: re-check block/status/verify/overlap → abort(409|403)
    JoinRequest::where('post_id', $post->id)->where('status', 'pending')->update(['status' => 'declined']);
    Chat::create([...]); // unique index chặn trùng
    // system message, accepted_count++, notify
});
```

## 4. Hủy sau accept & các luồng bổ sung
| Tình huống | Xử lý |
|---|---|
| Chủ bài hủy | Post → `cancelled`; join accepted → `cancelled`; chat `read_only` ngay |
| Người được chọn hủy **trước** cutoff | Post → `open` (join_count tính lại); join → `cancelled`; chat `read_only` |
| Người được chọn hủy **sau** cutoff | Post → `cancelled` |
| Mọi hủy sau accepted | Người hủy: `$inc stats.cancel_after_accept_count`; **không join lại đúng bài**; thông báo bên còn lại |
| Post đến giờ chiếu chưa chọn | Job `posts:expire`: post → `expired`, join pending → `expired`; API chọn người vẫn 409 nếu gọi sau |
| Showtime bị hủy / Sheet không còn suất | `is_cancelled=true`; post → `cancelled`; join pending → `expired`, accepted → `cancelled`; chat `read_only`; **không tính vào số lần hủy của ai**. Đổi giờ = hủy suất cũ + tạo suất mới |
| Giới hạn OTP | 5 lần sai/giờ, 3 lần gửi lại/giờ; mã hết hạn 10 phút; lưu hash |
| Ban bị lách bằng email mới | `hmac_hash` email chuẩn hóa; chỉ là rào cản |
| Xóa tài khoản | `deleted_at`; hủy post open + join pending; chat `read_only`; ẩn danh hóa PII; xóa device token; giữ bản ghi an toàn theo retention |

## 5. Xếp hạng người join (F08)
Chỉ gợi ý thứ tự; chủ bài tự quyết.
```
Score = Jaccard × 0.70 + (rating_avg / 5) × 0.15 + ColdStart × 0.15
Jaccard   = |A ∩ B| / |A ∪ B|        (trên tập genre_code, đối xứng)
rating_avg = rating.sum / rating.count   (chỉ rating đã reveal)
ColdStart = 1 nếu rating.count < 3, ngược lại 0
```
Ví dụ: A {action, comedy, horror}, B {action, comedy, romance} → giao 2, hợp 4 → Jaccard **0.5**.
Tính **lúc đọc** (dữ liệu luôn mới). Mục tiêu < 2 giây. `rank.breakdown` chỉ lưu nếu cần giải thích. Nếu `rating.count = 0`, `rating_avg` coi là 0 (cold-start bù lại). UI hiển thị "Độ hợp gu phim 88%" = Jaccard làm tròn.

## 6. Rating 2 chiều ẩn (F14, blind rating)
- Mỗi bên chấm sao 1–5 + tối đa 4 thẻ tích cực + nhận xét ≤300 ký tự (tùy chọn), **sau suất chiếu**.
- Rating **ẩn** (`revealed_at = null`) cho đến khi **đủ cặp** hoặc **sau 72h** từ khi kết thúc suất.
- API **không bao giờ** trả rating chưa reveal (kể cả cho người nhận).
- **Reveal idempotent:** `update ratings set revealed_at=now where _id=:id and revealed_at is null`; chỉ khi `modifiedCount==1` mới `$inc` `rating.sum`, `rating.count`, `rating.tag_counts.<tag>` của `ratee`.
- Điểm TB **không** cập nhật lúc tạo rating (tránh lộ rating đang ẩn).
- Job/handler reveal chạy khi: rating thứ hai được gửi, hoặc lazy khi đọc sau 72h, hoặc job dọn.
- Chỉ thẻ **tích cực** (tránh trả đũa); hành vi xấu dùng Report (F13).
- `stats.completed_count++` khi buổi hẹn hoàn tất.

## 7. Chat (F10)
- Chỉ tạo sau khi chủ bài chọn; chỉ 2 thành viên; **không ai nhắn được cho người lạ**.
- `read_only` sau `end_time + 24h` — Policy kiểm tra `now ≥ read_only_at` trực tiếp (lazy).
- Nội dung xóa sau `end_time + 3 ngày` bằng TTL trên `messages.expire_at`; Policy lọc thêm `expire_at > now` khi đọc (TTL quét ~1 phút/lần).
- **Chỉ văn bản.** Không ảnh/file.
- **Hold khi có report:** khi tạo report liên quan chat: `chats.hold_count++`, set `chats.expire_at = null` và `messages.expire_at = null` (TTL bỏ qua document không có `expire_at`). Khi đóng report: `hold_count--`; về 0 thì khôi phục `expire_at`. Dùng `hold_count` (không dùng boolean) vì có thể có nhiều report cùng lúc.
- **Snapshot bằng chứng:** khi report, copy ≤50 tin gần nhất vào `reports.evidence`, giữ đến xử lý xong + 30 ngày.
- Tin `system`: `matched`, `read_only_soon`, `read_only`, `deleting_soon` — do server tạo.
- UI: nút Report luôn hiện; banner "chat sẽ xóa vào dd/mm", nhắc ở mốc còn 24h.
- Giới hạn cần ghi trong báo cáo: tin đã bị TTL xóa trước khi có report thì không khôi phục; client có thể còn cache.

## 8. Report & kiểm duyệt (F13, F15)
```
Report (user | post | join_request) → đếm distinct reporter của người bị báo cáo
  → đạt ngưỡng: vào hàng chờ ưu tiên (KHÔNG tự khóa)
  → Admin xem (kèm evidence) → warn → suspend → ban, ghi moderation_actions
```
- Chống thao túng: unique partial `(reporter_id, target_type, target_id)`; rate limit report/ngày; đếm **distinct reporter**.
- Mọi lần admin mở report, xem evidence, xử lý → ghi `audit_logs` (không kèm nội dung tin nhắn).
- Admin vẫn thấy người báo cáo (không hứa "ẩn danh 100%").
- Report `gender_fraud`: admin xác nhận đúng ⇒ ban ngay + ghi `banned_identifiers`.

## 9. Chống giả mạo giới tính (3 lớp)
1. **Ngăn chặn:** 2 ảnh theo lệnh ngẫu nhiên; khóa `gender` sau verify; **đổi avatar ⇒ reset `photo_verified_at`** (mất quyền đăng/join đến khi verify lại).
2. **Phát hiện:** report `gender_fraud`; job `abuse:scan` (tài khoản mới có tỷ lệ join/bị từ chối cao, đổi thông tin sớm).
3. **Xử lý:** admin xác nhận ⇒ ban + `banned_identifiers`.
Photo Verification chỉ xác nhận ảnh đại diện khớp người đăng nhập, **không** xác minh giới tính sinh học.

## 10. Photo Verification (F03)
- Cần consent `biometric`. Tối đa 5 lần thất bại/24h (`verification_attempts`).
- Lệnh ngẫu nhiên (vd "Nghiêng đầu sang phải", "Mỉm cười") — 2 ảnh theo 2 lệnh.
- Gọi face service so khớp với avatar; xử lý trong `try/finally` **xóa selfie + descriptor** dù thành công hay lỗi.
- Chỉ lưu cờ kết quả → `photo_verified_at`.
- UI không được ghi "3D Depth Sensing" hay "liveness video" — thực tế là so khớp + lệnh ngẫu nhiên.

## 11. Xác minh tuổi (F01)
Server tính tuổi từ `birth_date`, chặn <18; bắt buộc tick điều khoản. **Tự khai**, nói rõ trong báo cáo/UI (không ghi "18+ đã xác minh" như đã kiểm giấy tờ). `age_verify_method = self_declared`.

## 12. Nạp dữ liệu Sheet → MongoDB
```
Google Sheet (movie, genres, cinema, address, start_time, format)
  → Publish to web (CSV) → php artisan sync:showtimes
  → Http::get → parse → validate → upsert movies, cinemas, showtimes
```
- Validate dòng thiếu dữ liệu và định dạng giờ (Carbon); `title_slug`/`slug` chuẩn hóa để upsert; `end_time = start_time + duration_min`.
- Showtime upsert theo `(movie_id, cinema_id, start_time, format)`; suất biến mất ⇒ `is_cancelled=true`, **không xóa**.
- Ghi `sync_runs` (created/updated/skipped/errors/cancelled_showtimes, ≤20 `error_samples`). Admin cũng dán CSV, nhận báo cáo tương tự (ghi `audit_logs` `csv_import`).

## 13. Giới hạn SOS (tóm tắt — chi tiết ở SAFETY_PRIVACY.md)
Tối đa 3 phiên/24h; 1 phiên active; mặc định 2h, gia hạn +1h/lần, tối đa 6h (`extend_count ≤ 5`).
