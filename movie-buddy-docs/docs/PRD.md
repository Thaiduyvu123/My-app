# PRD — Movie Buddy (v10)

## 1. Bài toán
Nhiều người muốn xem phim rạp nhưng thiếu bạn đồng hành. Một người đăng bài (phim + rạp + suất), người khác xin join, chủ bài chọn người phù hợp nhất. Không nhất thiết là hẹn hò; có thể là kết bạn theo sở thích.

**Khác Tinder:** ghép theo *sự kiện* (phim + rạp + suất), không theo hồ sơ. **Bỏ matching tự động** để giải quyết thanh khoản và trao quyền quyết định cho người dùng; thuật toán chỉ *gợi ý thứ tự*.

## 2. Mục tiêu MVP
Chạy đủ luồng end-to-end: đăng ký (18+) → verify ảnh → đăng bài/join → chọn → chat có thời hạn → đánh giá 2 chiều. Thể hiện thiết kế ưu tiên an toàn và dữ liệu cá nhân.

## 3. Luồng hệ thống
```
Đăng ký (OTP + ngày sinh 18+ + consent) → Hồ sơ + Photo Verification
  → Feed → Đăng bài (F05) hoặc Join (F07)
  → Chủ bài xem danh sách join đã xếp hạng → Chọn người (nhiều lớp phòng thủ)
  → Post matched, các join còn lại declined → Chat active
  → Buổi hẹn (SOS / chia sẻ vị trí nếu cần)
  → Sau suất chiếu: đánh giá 2 chiều → chat read_only (24h) → xóa nội dung (3 ngày)
```

## 4. Chức năng
| Mã | Chức năng | Mô tả | Ưu tiên |
|---|---|---|---|
| F01 | Đăng ký/đăng nhập | OTP email; ngày sinh, chặn <18; consent `terms`, `privacy` | Bắt buộc |
| F02 | Hồ sơ | Ảnh, giới tính (khóa sau verify), giới tính mong muốn, thể loại yêu thích | Bắt buộc |
| F03 | Photo Verification | So khớp khuôn mặt + 2 ảnh theo lệnh ngẫu nhiên; cần consent `biometric`; tối đa 5 lần thất bại/24h | Bắt buộc |
| F04 | Chọn rạp gần nhất | Truy vấn địa lý trên tọa độ rạp (`2dsphere`) | Bắt buộc |
| F05 | Đăng bài | Phim + rạp + suất + ghi chú + `audience`; tùy chọn `cost_split`, `vibe_tags` (≤5), `max_join_requests` (mặc định 10); `women_only` chỉ nữ đã verify mới tạo được | Bắt buộc |
| F06 | Feed | Lọc rạp/phim/suất/thành phố; ẩn theo block hai chiều và giới tính mong muốn | Bắt buộc |
| F07 | Join | Lời nhắn tùy chọn (≤300 ký tự); ≤10 pending/người và giới hạn join/ngày; mỗi bài ≤ `max_join_requests` pending; không join sau `join_cutoff_at` | Bắt buộc |
| F08 | Xếp hạng người join | Jaccard + rating + cold-start | Bắt buộc |
| F09 | Chọn người | Thao tác nguyên tử nhiều lớp, đóng bài, từ chối join còn lại | Bắt buộc |
| F10 | Chat | Chỉ tạo sau khi chọn; `read_only` sau 24h; xóa nội dung sau 3 ngày | Bắt buộc |
| F11 | SOS khi nguy hiểm | Nhấn giữ 3s + đếm ngược 5s (hủy được); mở sẵn `sms:` kèm link theo dõi; phiên trực tiếp 2h, gia hạn tối đa 6h; "Tôi an toàn" để kết thúc | Bắt buộc |
| F12 | Chia sẻ vị trí buổi hẹn | Chủ động, người xem trong app (≤2), chỉ giữ vị trí cuối, tự hết hạn | Nên có |
| F13 | Report/Block | Report người, bài, lời join; chống spam và brigading | Bắt buộc |
| F14 | Đánh giá 2 chiều | Ẩn đến khi đủ cặp hoặc sau 72h; sao 1–5 + thẻ tích cực nhanh | Bắt buộc |
| F15 | Admin Panel | Duyệt report + evidence, quản lý user, SOS, nhập CSV + lịch sử sync, audit log | Bắt buộc |

F11 khác F12: F11 là khẩn cấp, người xem qua **link trình duyệt** (không cần app); F12 là chủ động, người xem **trong app**.

## 5. Phi chức năng
- **An toàn:** không lưu lịch sử vị trí; xóa selfie + descriptor ngay sau xử lý (chỉ giữ cờ kết quả); mã hóa SĐT liên hệ khẩn cấp ở tầng ứng dụng.
- **Pháp lý:** thiết kế "theo tinh thần" Luật BVDLCN 91/2025/QH15 và NĐ 356/2025/NĐ-CP (chưa kiểm chứng văn bản gốc — cần đối chiếu trước khi viết điều khoản); consent theo mục đích; bảng retention công bố; có quy trình xóa/ẩn danh tài khoản. Không phải hồ sơ tuân thủ đầy đủ.
- **Hiệu năng:** xếp hạng người join < 2 giây với dữ liệu test.
- **Chi phí:** toàn bộ trong free tier.
- **Mở rộng:** thêm rạp/thành phố không đổi schema (`city_code`).

## 6. Nạp dữ liệu phim/rạp/suất
Google Sheets (publish CSV) → `php artisan sync:showtimes` → validate → upsert `movies`, `cinemas`, `showtimes`. Chạy hằng giờ qua Scheduler; admin cũng dán CSV trực tiếp. Chi tiết ở `BUSINESS_RULES.md` mục 9.

## 7. Tiêu chí thành công
- Luồng end-to-end chạy được, feed có seed data.
- Chọn người đúng khi tranh chấp; rating ẩn đúng cơ chế; Jaccard đúng công thức.
- `db:check-invariants` sạch sau seed.
- SOS: phiên trực tiếp chạy, `/track` hiển thị, kết thúc xóa vị trí.
- Admin duyệt report có evidence; thao tác ghi `audit_logs`.
- Có điều khoản, màn consent, bảng retention.

## 8. Chỉ số sản phẩm (tính từ timestamp từng bước)
Tỷ lệ bài được chọn · thời gian tới match · tỷ lệ hủy sau accept · tỷ lệ report · tỷ lệ hoàn tất đánh giá · thời gian xử lý report.
