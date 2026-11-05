# SAFETY & PRIVACY

## 1. Nguyên tắc
Thu thập tối thiểu · không lưu lịch sử vị trí · xóa sinh trắc ngay sau xử lý · consent theo mục đích · mọi quyền kiểm tra ở server · thừa nhận giới hạn trung thực (đây là prototype, không thay 113).

## 2. SOS & vị trí khi nguy hiểm (F11)
**Luồng:** nhấn giữ 3s → đếm ngược 5s (hủy được) → tạo phiên (`sos_alerts`, `live_until = now+2h`) → app **mở sẵn** `sms:` tới người liên hệ khẩn cấp kèm `tracking_url` (người dùng **tự bấm gửi**) → app cập nhật vị trí mỗi 10–15s → người liên hệ mở link trên trình duyệt, bản đồ tự làm mới 10s → kết thúc khi bấm "Tôi an toàn" hoặc hết hạn (vị trí trực tiếp bị xóa).

| Quyết định | Nội dung |
|---|---|
| Người nhận | Người liên hệ khẩn cấp + admin. **Không tự báo người đi cùng** |
| Token | 32 byte ngẫu nhiên; DB chỉ lưu SHA-256; link vô hiệu khi phiên kết thúc |
| Thời gian | Mặc định 2h, gia hạn +1h/lần, tối đa 6h; ≤3 phiên/24h; chỉ 1 phiên active |
| Dữ liệu | Chỉ lưu vị trí lúc bấm + vị trí cuối (ghi đè); không lưu đường đi, pin, WiFi, thiết bị |
| Trang theo dõi | Chỉ lộ tên hiển thị + vị trí + thời điểm cập nhật; `noindex`; rate limit |
| Điều kiện | Consent `location` còn hiệu lực + đã đặt liên hệ khẩn cấp |
| Admin | Danh sách phiên đang hoạt động; `handled`, `admin_closed`; tạo report từ SOS |

**Phải ghi rõ trong app/báo cáo:** hỗ trợ trong phạm vi prototype, không thay thế 113/114/115; SMS do người dùng bấm gửi; app bị OS tắt nền (nhất là iOS) có thể gián đoạn cập nhật vị trí; vị trí cuối vẫn hiện kèm thời điểm cập nhật; cần thử máy thật Android + iOS.
**Không có:** nút gọi công an/quản lý rạp, "SMS tự động", "24/7", kích hoạt khi khóa màn hình/nút nguồn.

## 3. Consent
Bốn loại riêng: `terms`, `privacy`, `biometric`, `location` (+ `marketing` tùy chọn, không chặn dịch vụ). Mỗi lần đồng ý/rút = bản ghi mới có `version`. Màn consent hiển thị **bảng retention** (xem DATABASE.md §6). Không tick sẵn các mục bắt buộc ngầm.

## 4. Sinh trắc học
Selfie + descriptor xử lý trong bộ nhớ/tạm, xóa ở `finally`. Chỉ lưu cờ `photo_verified_at`. Không lưu hash khuôn mặt (kể cả cho ban).

## 5. Dữ liệu liên hệ khẩn cấp
SĐT mã hóa ở tầng ứng dụng; không log; chỉ giải mã khi tạo `sms:` link ở client của chính user (API `/me` trả đã che `0903•••789` cho hiển thị).

## 6. Xóa tài khoản
`deleted_at`; hủy post open + join pending; chat `read_only`; ẩn danh hóa PII (tên, email, ảnh, SĐT, ngày sinh); xóa device token; giữ bản ghi an toàn cộng đồng (`moderation_actions`, `banned_identifiers`, report đang xử lý) theo retention và nêu trong điều khoản.

## 7. Audit
Admin xem evidence/xử lý report/SOS/nhập CSV/khóa tài khoản ⇒ `audit_logs` (chỉ thêm, 12 tháng, không PII/nội dung tin nhắn).

## 8. Pháp lý
Thiết kế "theo tinh thần" Luật BVDLCN 91/2025/QH15 và NĐ 356/2025/NĐ-CP (hiệu lực 01/01/2026, thay NĐ 13/2023). **Chưa kiểm chứng độc lập** — đối chiếu văn bản gốc trước khi viết điều khoản. UI không được dẫn chiếu NĐ 13/2023. Không phải hồ sơ tuân thủ đầy đủ (DPO, đăng ký...).

## 9. Câu chữ UI cấm / cần sửa (không hứa quá thực tế)
| Không viết | Viết |
|---|---|
| "CCCD verified", "Căn cước công dân" | "Photo Verified" |
| "18+ đã xác minh" | "Đã tự khai 18+" |
| "3D Depth Sensing", "Live Production" | Bỏ |
| "Người báo cáo ẩn danh/mã hóa 100%" | "Người bị báo cáo không thấy danh tính người báo cáo" (admin vẫn thấy) |
| "SMS tự động gửi qua gateway", "Admin Safety 24/7" | "Mở sẵn tin nhắn, bạn bấm gửi" |
| "Cinephile Verified" | Bỏ |
