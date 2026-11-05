# DESIGN SYSTEM — "Cyber Twilight"

> Bộ UI gốc (Stitch) là HTML/Tailwind; app là React Native. **Chỉ lấy bảng màu/typography/phong cách**, dựng lại bằng component RN. Mã màu dưới đây **lấy mẫu từ ảnh chụp màn hình** (xấp xỉ) — chỉnh lại nếu có `DESIGN.md` gốc từ Stitch.

## 1. Phong cách
Tối (dark-only), nền xanh đêm rất sẫm, điểm nhấn hồng-san hô nóng, card bo góc lớn, viền mảnh, glow nhẹ quanh phần tử quan trọng (SOS, CTA). Cảm giác "rạp chiếu phim ban đêm", an toàn nhưng không lạnh.

## 2. Màu (tokens)
| Token | Hex (xấp xỉ) | Dùng cho |
|---|---|---|
| `bg` | `#081020` | Nền màn hình |
| `surface` | `#101828` | Card cấp 1 |
| `surfaceAlt` | `#101830` | Card cấp 2, input |
| `border` | `#283048` | Viền card/input |
| `primary` | `#F84870` | CTA chính, tab "Tạo bài", SOS, sao, chip chọn |
| `primarySoft` | `primary @ 15–20%` | Nền chip/badge hồng |
| `accentBlue` | `#3030D0`–`#4060E0` | Nút "Chọn bạn này", gradient CTA OTP (hồng → xanh) |
| `info` | `#30A0E0` (cyan) | Badge verified, GPS, link |
| `success` | `#30C080` | "Đã xem", hoàn thành |
| `warning` | `#F0A030` | Cảnh báo, đếm ngược |
| `danger` | `#F84870` đậm / `#D02040` | Report, xóa tài khoản |
| `textPrimary` | `#F0F4FF` | Chữ chính |
| `textMuted` | `#8890A8` | Chữ phụ, caption |

Quy tắc: không tạo màu mới ngoài token; trạng thái dùng cả **icon + chữ**, không chỉ dựa vào màu.

## 3. Typography
Sans-serif hình học dạng **Inter** (hoặc system font nếu chưa cài). Tiêu đề đậm 20–28, tiêu đề card 16–18 semi-bold, body 14, caption 11–12, nhãn section **IN HOA nhỏ** (letter-spacing rộng, `textMuted`). Số liệu (điểm, km, đếm ngược) dùng chữ đậm, `tabular-nums`.

## 4. Spacing, hình khối
Lưới 4pt (4/8/12/16/24). Padding màn 16. Card radius 16–20, input/nút radius 12, chip/pill radius full. Viền 1px `border`. Glow: shadow màu `primary` opacity ~0.4, blur 16–24 (chỉ SOS và CTA chính). Nút chính cao ≥48.

## 5. Component dùng chung
- **AppHeader:** logo Movie Buddy + chip `18+` + nút **SOS** tròn đỏ/hồng + chuông + avatar.
- **BottomTab (4):** Khám phá · **Tạo bài** (nút giữa tròn hồng nổi) · Yêu cầu & Chat · Cá nhân.
- **Card / SectionHeader** (icon + nhãn IN HOA + thao tác phụ).
- **Chip** (chọn được; trạng thái chọn = nền `primary`), **Badge** (Photo Verified, Women-Only, thể loại, format).
- **PrimaryButton** (hồng; biến thể gradient hồng→xanh cho OTP), **SecondaryButton** (viền), **GhostButton**, **DestructiveButton**.
- **Avatar** có tick verify; **StarRating** (5 sao hồng, trạng thái trống hiện "—" chứ không 5.0).
- **OtpInput** (6 ô), **Stepper/Progress** (4 đoạn trong màn tạo bài), **Countdown**, **Toggle**, **Slider khoảng** (tuổi).
- **PostCard** (poster, tên phim, rạp + km, giờ, ghi chú trích dẫn, chủ bài + tick, `join_count`, nút "Xin tham gia").
- **JoinCard** (avatar, tên/tuổi, Photo Verified, sao, % hợp gu, thẻ thể loại trùng, lời nhắn, [Từ chối] [Chọn bạn này]).
- **ChatBubble** (mình: nền xanh-tím; người kia: `surfaceAlt`; tin system ở giữa, nền mờ).
- **MapView** (Leaflet/OSM trong WebView hoặc `react-native-maps` với OSM tile) + marker cụm rạp có khoảng cách.
- **EmptyState / ErrorState / Skeleton** cho mọi danh sách.
- **SosButton** (tròn lớn có glow, nhấn giữ 3s có vòng tiến độ).

## 6. Admin (web, ReactJS)
Layout: sidebar trái (Thống kê tổng quan · Duyệt report vi phạm · Quản lý người dùng* · Giám sát SOS · Đồng bộ phim & rạp · Cấu hình & retention), topbar có ô tìm kiếm và avatar admin. Cùng token màu như mobile; thẻ KPI, biểu đồ đường (7 ngày), bảng dày thông tin, panel chi tiết bên phải. (*màn Quản lý người dùng chưa có trong UI — cần thiết kế bổ sung.)

## 7. Nội dung/ngôn ngữ
Tiếng Việt, giọng thân thiện, rõ ràng. Không phô trương tính năng bảo mật vượt thực tế (xem `SAFETY_PRIVACY.md` §9).
