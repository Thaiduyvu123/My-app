# SCREENS — màn hình, chức năng và điểm lệch UI

Ảnh tham chiếu trong `docs/ui-reference/`. Tên file đã chuyển sang ASCII.

## 1. Mobile (15 màn trong bộ UI + các màn còn thiếu)
| # | Màn | Chức năng | Ảnh | Ghi chú đối chiếu |
|---|---|---|---|---|
| 1 | Đăng nhập OTP email | F01 | `01-login-otp.png` | **Bỏ** Google/Apple; rate limit OTP; hết hạn 10 phút |
| 2 | Xác minh tuổi & điều khoản | F01 | `02-age-consent.png` | Tuổi tự khai; consent `terms`, `privacy`; bỏ "ưu đãi vé" hoặc ghi tùy chọn `marketing`; sửa dẫn chiếu luật |
| 3 | Hồ sơ cá nhân | F02 | `03-profile.png` | Khóa giới tính sau verify; "Xóa lịch sử vị trí" luôn bật tự động; hiển thị `completion_rate` |
| 4 | Photo Liveness | F03 | `04-photo-liveness.png` | Bỏ "3D Depth Sensing"; 2 lệnh ngẫu nhiên; consent `biometric` |
| 5 | Xác nhận đăng xuất an toàn | F01 | `05-logout.png` | Không hiển thị IP/2FA; dùng tên thiết bị + `last_used_at` |
| 6 | Chọn rạp gần nhất | F04 | `06-cinema-selection.png` | Leaflet/OSM; nhãn format gộp vào enum |
| 7 | Khám phá (Feed) | F06 | `07-feed.png` | Chỉ hiện **số** người xin tham gia, bỏ avatar người join; bỏ "Còn 12 vé" |
| 8 | Đăng kế hoạch xem phim | F05 | `08-create-post.png` | `audience`, `cost_split`, `vibe_tags`, `max_join_requests`; lời nhắn 300 ký tự; độ tuổi mong muốn tùy chọn, mặc định tắt; hiển thị `join_cutoff_at` |
| 9 | Chi tiết kế hoạch + Quản lý người tham gia | F08, F09 | `09-post-detail-joins.png` | Xếp hạng Jaccard + rating + cold start; "Chọn bạn này" gọi API chọn nguyên tử |
| 10 | Hội thoại trực tiếp (Chat) | F10 | `10-chat.png` | **Bỏ ảnh trong chat**; banner read_only/xóa; nút SOS, chia sẻ vị trí |
| 11 | SOS khẩn cấp & chia sẻ vị trí | F11, F12 | `11-sos-location.png` | **Thiết kế lại theo SAFETY_PRIVACY §2**: đếm ngược, mở `sms:`, link theo dõi, gia hạn, "Tôi an toàn"; bỏ pin/WiFi, "Admin 24/7", nút gọi |
| 12 | Đánh giá 2 chiều | F14 | `12-blind-rating.png` | Sao + thẻ tích cực (≤4) + 300 ký tự; hiển thị trạng thái ẩn đến khi reveal/72h |
| 13 | Báo cáo vi phạm | F13 | `13-report.png` | **Bỏ tải ảnh**; bằng chứng = snapshot 50 tin; không ghi "ẩn danh 100%" |

### Màn còn thiếu (phải thiết kế/viết thêm)
Ưu tiên cao: **SOS mới** (theo flow trên) · **trang web `/track/{token}`** · **hủy sau accept**.
Còn lại: tab "Yêu cầu & Chat" (danh sách join/chat của tôi) · thiết lập liên hệ khẩn cấp · quản lý block · thông báo · màn consent vị trí · trạng thái trống/lỗi/loading cho mọi danh sách · admin "Quản lý người dùng".

## 2. Admin (4 màn trong bộ UI)
| Màn | Chức năng | Ảnh | Ghi chú |
|---|---|---|---|
| Dashboard tổng quan | F15 | `a1-admin-dashboard.png` | KPI từ timestamp/`daily_stats`; bỏ "Live Production", "AI kiểm duyệt tự động" |
| Duyệt report & bằng chứng | F15.1 | `a2-admin-reports.png` | Bỏ ảnh bằng chứng; bỏ "ban HMAC Device ID & CCCD" ⇒ ban theo HMAC **email**; ghi `audit_logs` khi mở/xem |
| Giám sát cảnh báo SOS | F15.3 | `a3-admin-sos.png` | Bỏ nút gọi công an/quản lý rạp và "SMS tự động"; có `handled`, `admin_closed`; vị trí cuối chỉ khi phiên còn hiệu lực |
| Đồng bộ lịch chiếu CSV | F15.4 | `a4-admin-sync.png` | Hiển thị `sync_runs` (created/updated/skipped/errors, ≤20 lỗi mẫu) |

## 3. Điểm lệch UI cần sửa (mức Cao trước khi nộp wireframe)
| Mức | Lệch | Sửa theo plan |
|---|---|---|
| Cao | Badge "Căn cước công dân verified"; ban "Device ID & CCCD" | "Photo Verified"; ban theo HMAC email |
| Cao | SOS: "SMS tự động qua gateway", nút gọi công an/quản lý rạp, "24/7" | Người dùng tự bấm gửi; bỏ nút gọi; bỏ "24/7"; thêm đếm ngược, trang theo dõi, gia hạn, "Tôi an toàn" |
| Cao | Feed hiện avatar người đã đăng ký tham gia | Chỉ hiện `join_count` |
| Cao | Dẫn chiếu NĐ 13/2023 | Luật 91/2025/QH15 + NĐ 356/2025/NĐ-CP (đối chiếu gốc) |
| Cao | Ảnh trong chat tự hủy 24h; report tải 5 ảnh | Bỏ; chỉ văn bản; evidence là snapshot 50 tin |
| Cao | "3D Depth Sensing", "18+ đã xác minh", "báo cáo ẩn danh/mã hóa 100%", "Live Production", "Cinephile Verified" | Đổi lời cho đúng thực tế |
| TB | Đăng nhập Google/Apple, 2FA, IP, consent "ưu đãi vé", "AI kiểm duyệt tự động" | Bỏ hoặc ghi hướng phát triển |
| TB | Độ tuổi mong muốn, "Còn 12 vé", điểm tin cậy, pin/WiFi trong SOS | Tuổi: tùy chọn, mặc định tắt; bỏ số vé và pin/WiFi; điểm tin cậy tính từ rating + `completion_rate` |
| Thấp | Toggle xóa lịch sử vị trí; lời nhắn 250 ký tự; sao trống hiện 5.0; nhãn RealD/Laser | Luôn tự xóa; 300 ký tự; hiện trạng thái trống; gộp vào enum `format` |
