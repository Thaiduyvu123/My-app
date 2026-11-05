# TESTING

> Plan nhắc tới bộ **TC01–TC43** nằm trong "file 03 (Phụ lục kỹ thuật)", không có trong bộ tài liệu này. Dưới đây là các kịch bản **bắt buộc** suy ra từ plan; mã TC được nhắc trong plan ghi kèm. Hãy ánh xạ sang danh sách TC chính thức khi có.

## 1. Công cụ
Backend: PHPUnit (Feature + Unit), DB test riêng (replica set). Mobile/Admin: Jest + React Testing Library. CI: GitHub Actions chạy PHPUnit + Jest.

## 2. Kịch bản bắt buộc
**Chọn người / đua (TC07, TC16–19)**
- Hai request chọn người khác nhau cho cùng post chạy đồng thời ⇒ đúng 1 thành công, 1 trả 409; chỉ 1 chat.
- Chọn join không còn `pending` ⇒ 409. Chọn sau `join_cutoff_at` ⇒ 409.
- Chọn khi người join vừa bị block / bị khóa / mất verify / trùng giờ ⇒ từ chối (lớp 5).
- Chạy lại cả **có và không có transaction**: unique index `chats` vẫn chặn chat thứ hai.
- Sau chọn: post `matched`, `join_count=0`, các join còn lại `declined`.

**Rating (TC10–12)**
- Rating mới tạo **không** đổi `rating.sum/count` của người nhận.
- API không trả rating chưa reveal (kể cả cho người nhận).
- Reveal khi đủ cặp; reveal sau 72h; **reveal hai lần không cộng đôi** (idempotent).
- `tag_counts` chỉ tăng khi reveal.

**Quyền & điều kiện**
- `women_only`: nam hoặc nữ chưa verify tạo ⇒ 403; xem/join ⇒ 403/ẩn khỏi feed.
- Chưa verify tuổi/ảnh ⇒ không đăng/join.
- Đổi avatar ⇒ `photo_verified_at = null` ⇒ không đăng/join cho đến khi verify lại.
- Block hai chiều: ẩn feed, không join, không thấy trong danh sách join.
- Join: trùng (unique), >10 pending, `join_count ≥ max_join_requests` ⇒ 409; quá cutoff ⇒ 409; trùng giờ ⇒ 409.
- Tuổi <18 khi đăng ký ⇒ 422. OTP: 5 sai/giờ, 3 gửi lại/giờ, hết hạn 10 phút.

**Chat**
- Không tạo được tin cho người lạ/không phải thành viên; client không tạo được tin `system`.
- Sau `read_only_at` không gửi được (kiểm tra lazy, không phụ thuộc job).
- Report ⇒ `hold_count++`, `expire_at=null`; **nhiều report** đóng lần lượt: chỉ khi `hold_count=0` mới khôi phục `expire_at`.
- Policy lọc `expire_at > now` khi đọc dù TTL chưa quét.

**Report / Admin**
- Spam report cùng `(reporter,target)` bị unique chặn; đủ ngưỡng distinct reporter ⇒ vào hàng chờ, **không tự khóa**.
- Admin mở report/xem evidence/xử lý ⇒ có `audit_logs`; model `AuditLog` không có update/delete; meta không chứa PII.
- Ban ⇒ `banned_identifiers` HMAC email chuẩn hóa (alias `+x`, dấu chấm Gmail).

**SOS (TC31–TC43)**
- Cần consent `location` + có liên hệ khẩn cấp; thiếu ⇒ từ chối.
- Phiên thứ 2 khi đang active ⇒ 409; >3 phiên/24h ⇒ 429/409.
- Token thô chỉ trả một lần; DB chỉ có SHA-256; token sai/phiên kết thúc ⇒ 404 ở `/track`.
- `extend` tối đa `extend_count=5` (tổng 6h).
- `end` / hết hạn ⇒ `live_position = null`; job `sos:expire-live` chuyển `expired`.
- `/track` không lộ gì ngoài tên hiển thị + vị trí + thời điểm; có `noindex`; rate limit.
- Không có đường nào tự báo người đi cùng.
- `purge_at` chỉ đặt khi `ended` **và** `handled`.

**Danh mục / sync**
- `sync:showtimes` upsert theo khóa; suất biến mất ⇒ `is_cancelled=true` (post → cancelled, join → expired/cancelled, chat read_only, không tính vào số lần hủy ai).
- Dòng lỗi được bỏ qua + ghi `error_samples` (≤20).

**Xếp hạng**
- Jaccard ví dụ {action,comedy,horror} vs {action,comedy,romance} = 0.5.
- Cold-start khi `rating.count < 3`; thời gian < 2s với 100 join/dữ liệu test.

**Photo verification**
- Ảnh/descriptor bị xóa cả khi lỗi (`finally`); >5 thất bại/24h ⇒ khóa tạm.

## 3. Invariants
`php artisan db:check-invariants` kiểm tra 12 invariants ở `DATABASE.md §7`. Chạy sau seed và trong CI.

## 4. Thủ công trên máy thật
SOS Android + iOS (app ở nền, tắt màn hình, mất mạng ngắn); `sms:` mở đúng app tin nhắn kèm link; `/track` mở trên trình duyệt điện thoại người khác.
