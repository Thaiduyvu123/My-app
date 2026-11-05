# DECISIONS — nhật ký quyết định & giả định

Ghi vào đây mọi quyết định/giả định khi tài liệu thiếu hoặc mơ hồ. Mỗi mục: ngày, quyết định, lý do.

## Đã chốt (từ plan v10)
- Bỏ matching tự động; dùng `posts + join_requests + chats`.
- Jaccard đối xứng thay công thức cũ.
- `join_cutoff_at = start − 2h` (có thể đổi).
- Tuổi tự khai; Photo Verification không xác minh giới tính sinh học.
- OSM + Leaflet, OTP email, Expo Push, Reverb trên VM.
- SOS không tự báo người đi cùng; SMS do người dùng tự gửi.
- Chỉ thẻ rating tích cực.

## Cần chốt (mở)
- [ ] Thời hạn lưu `posts`, `join_requests`, metadata chat (đề xuất 12 tháng).
- [ ] Bật hay bỏ lọc theo tuổi (`seeking_age_range`, `host_age`, `age_range`) — cân nhắc nguy cơ loại trừ.
- [ ] Làm hay bỏ `marketing` consent, `daily_stats`, `notifications`.
- [ ] Ngưỡng distinct reporter để vào hàng chờ ưu tiên; số join/ngày tối đa.
- [ ] Đối chiếu văn bản gốc Luật 91/2025/QH15 và NĐ 356/2025/NĐ-CP trước khi viết điều khoản.
- [ ] Kiểm tra giá free tier (Oracle, Atlas M0, R2, Vercel) trước khi chốt.
- [ ] Face service: face-api.js tự host hay API bên thứ ba.

## Giả định của bộ tài liệu này
- File 02 (DB v9), 03 (phụ lục), 04 (flow), 05 (SOS) **không có** trong đầu vào: field đánh dấu "(suy luận)" trong `DATABASE.md`, endpoint trong `API_SPEC.md`, mã màu trong `DESIGN_SYSTEM.md` là tái dựng/xấp xỉ.
- Mobile dùng Expo (suy ra từ Expo Push + EAS Build).

## Nhật ký
| Ngày | Quyết định | Lý do |
|---|---|---|
| 2026-11-05 | Triển khai giao diện người dùng trên cả Expo (`mobile/`) và React/Vite (`web/`), bắt đầu với luồng xin tham gia. Hai giao diện hiện là bản xem trước dùng dữ liệu minh họa; chỉ nối gửi yêu cầu sau khi backend và xác thực sẵn sàng. | Người dùng chọn cả mobile và web, ưu tiên luồng xin tham gia; workspace ban đầu chưa có mã nguồn ứng dụng hoặc API. |
| 2026-11-05 | Laravel MongoDB 5.x dùng Atlas khi `DB_CONNECTION=mongodb`; SQLite models riêng chỉ phục vụ test/local. URI và password không commit. | Cấu hình extension và kết nối thật được tách khỏi code; API/test vẫn chạy độc lập khi không có Atlas credentials. |
