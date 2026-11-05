# CLAUDE.md — Movie Buddy

> File này được AI coding agent (Claude Code, Cursor...) đọc đầu tiên. Đọc hết trước khi viết bất kỳ dòng code nào.

## 1. Dự án là gì
**Movie Buddy** — app tìm bạn đi xem phim rạp. Người A đăng bài (phim + rạp + suất chiếu), người khác xin join, chủ bài chọn 1 người, hai bên chat có thời hạn, sau buổi xem đánh giá 2 chiều (blind rating). Đây là **đồ án cá nhân**, MVP, ưu tiên **an toàn người dùng (đặc biệt nữ giới)** và **dữ liệu cá nhân tối thiểu**.

- Người dùng: 18+, sinh viên/người đi làm.
- Hạ tầng: ưu tiên free tier.
- Ngôn ngữ UI: tiếng Việt. Code, tên field, enum, commit: tiếng Anh.

## 2. Tài liệu (đọc theo nhu cầu)
| Việc cần làm | Đọc |
|---|---|
| Hiểu sản phẩm, chức năng F01–F15 | `docs/PRD.md` |
| Stack, cấu trúc thư mục, deploy | `docs/ARCHITECTURE.md` |
| Tạo model/migration/index/seeder | `docs/DATABASE.md` |
| State machine, thao tác nguyên tử, xếp hạng, rating | `docs/BUSINESS_RULES.md` |
| Viết controller/route | `docs/API_SPEC.md` |
| SOS, consent, retention, xóa tài khoản | `docs/SAFETY_PRIVACY.md` |
| Làm UI mobile/admin | `docs/DESIGN_SYSTEM.md` + `docs/SCREENS.md` + `docs/ui-reference/*.png` |
| Biết tuần này làm gì | `docs/ROADMAP.md` |
| Viết test | `docs/TESTING.md` |
| Prompt mẫu theo từng giai đoạn | `docs/PROMPTS.md` |

Khi tài liệu mâu thuẫn nhau, thứ tự ưu tiên: **`DATABASE.md` / `BUSINESS_RULES.md` / `SAFETY_PRIVACY.md` > `PRD.md` > ảnh UI**. Ảnh UI chỉ là tham chiếu thị giác, có nhiều chỗ lệch plan (xem `docs/SCREENS.md` mục "Điểm lệch UI").

## 3. Stack
- **Mobile:** React Native (Expo), TypeScript.
- **Backend:** Laravel (REST) + MongoDB qua `mongodb/laravel-mongodb` (cần replica set để dùng transaction).
- **Auth:** Laravel Sanctum, đăng nhập bằng OTP email (không SMS, không Google/Apple).
- **Realtime chat:** Laravel Reverb.
- **Admin:** ReactJS (Vite) gọi cùng REST API.
- **Bản đồ:** OpenStreetMap + Leaflet (không dùng Google Maps).
- **Photo Verification:** microservice Node (face-api.js) hoặc API bên thứ ba.
- **Storage:** Cloudflare R2 hoặc Laravel Storage. **Push:** Expo Push.
- **Trang theo dõi SOS:** HTML tĩnh + Leaflet, Laravel phục vụ `/track/{token}`.

## 4. Quy ước bắt buộc
**Đặt tên & dữ liệu**
- Field/collection: `snake_case` tiếng Anh. `_id` là ObjectId.
- Thời gian lưu **UTC**, hậu tố `_at` (`created_at`, `expire_at`...). Hiển thị theo giờ VN ở client.
- Mọi trạng thái đặt tên `status`. Giá trị enum: tiếng Anh, không dấu, snake_case. **Không** lưu chuỗi tiếng Việt làm giá trị logic.
- Giá trị suy ra (`rating_avg`, `completion_rate`, trạng thái online) **không lưu**, tính khi đọc.

**Backend**
- Validate ở `FormRequest`; phân quyền ở `Policy/Gate`. Kiểm tra quyền ở server, không chỉ ẩn nút ở UI (vd `women_only` phải trả 403).
- Kiểm tra thời gian **trực tiếp** khi xử lý request (`expires_at`, `join_cutoff_at`, `read_only_at`, `live_until`). Job chỉ để dọn dẹp, không phải nguồn sự thật.
- Cấm mass-assignment các field: `join_count`, `stats.*`, `rating.*`, `live_*`, `share_token_hash`, `messages.type = system`. Dùng `$fillable` chặt, không dùng `$guarded = []`.
- Các thao tác đổi nhiều document (chọn người, reveal rating, hủy sau accept) phải nguyên tử: update có điều kiện + transaction + kiểm tra kết quả `modifiedCount`.
- Trả lỗi nghiệp vụ bằng mã HTTP đúng (409 xung đột trạng thái, 403 không đủ quyền, 422 validate).
- Không log PII, nội dung tin nhắn, selfie, token thô, số điện thoại.

**Mobile / Admin**
- TypeScript strict. Theme lấy từ `docs/DESIGN_SYSTEM.md`, không hard-code màu rải rác.
- Không copy HTML/Tailwind từ ảnh UI; dựng lại bằng component React Native.
- Mọi màn có trạng thái: loading, empty, error.

## 5. KHÔNG làm (ngoài phạm vi — đừng tự thêm)
- eKYC/CCCD, liveness video, xác minh tuổi bằng giấy tờ (tuổi là **tự khai**).
- Đăng nhập Google/Apple, 2FA, lưu/hiển thị IP.
- Gửi SMS tự động qua gateway (SOS chỉ **mở sẵn** `sms:` để người dùng tự bấm gửi).
- Nút "gọi công an/quản lý rạp", chữ "24/7", "AI kiểm duyệt tự động".
- Ảnh trong chat, ảnh chụp màn hình làm bằng chứng (chat chỉ văn bản; bằng chứng = snapshot ≤50 tin).
- Lưu lịch sử vị trí, đường đi, pin/WiFi/mã thiết bị, hash khuôn mặt.
- Lưu số vé còn trống, scraping website rạp, API đặt vé thật.
- Kích hoạt SOS khi khóa màn hình/nút nguồn.
- Tự báo SOS cho **người đi cùng** (họ có thể là nguồn nguy hiểm).
- Tự khóa tài khoản khi đủ ngưỡng report (chỉ đưa vào hàng chờ admin).

## 6. Lệnh thường dùng (điều chỉnh theo repo thực tế)
```bash
# backend
cd backend && composer install && php artisan migrate && php artisan db:seed
php artisan serve                      # hoặc XAMPP
php artisan reverb:start
php artisan test                       # PHPUnit
php artisan db:check-invariants        # kiểm tra invariants (tự viết, xem DATABASE.md mục 7)
php artisan sync:showtimes             # nạp CSV từ Google Sheets
php artisan schedule:work

# mobile
cd mobile && npx expo start && npm test

# admin
cd admin && npm run dev && npm test
```
MongoDB local phải chạy replica set: `mongod --replSet rs0` rồi `rs.initiate()`.

## 7. Cách làm việc với tôi (agent)
1. **Một việc một lượt.** Làm theo tuần/đầu việc trong `docs/ROADMAP.md`; không nhảy cóc sang tính năng sau.
2. Trước khi code một tính năng: nêu ngắn gọn (3–6 dòng) bạn hiểu yêu cầu thế nào, file nào sẽ đụng, rồi mới làm.
3. Viết **migration + model + policy + FormRequest + test** cùng nhau cho mỗi tính năng backend.
4. Thấy tài liệu thiếu/mơ hồ → **hỏi**, đừng đoán. Nếu buộc phải giả định, ghi giả định vào `docs/DECISIONS.md`.
5. Sau mỗi tính năng: chạy test, chạy `db:check-invariants`, báo kết quả thật (không tuyên bố "xong" khi chưa chạy).
6. Không refactor ngoài phạm vi yêu cầu; không thêm dependency khi chưa cần (free tier).
7. Commit nhỏ, thông điệp tiếng Anh dạng `feat(posts): add join_count`.

## 8. Definition of Done cho một tính năng
- [ ] Đúng luật nghiệp vụ trong `BUSINESS_RULES.md` và không vi phạm mục "KHÔNG làm".
- [ ] Có Policy/FormRequest, trả đúng mã lỗi.
- [ ] Có test cho happy path + ít nhất 1 test xung đột/quyền.
- [ ] Index cần thiết đã tạo trong migration.
- [ ] Không rò dữ liệu nhạy cảm qua API (rating chưa reveal, danh sách join cho người khác, vị trí chính xác ở feed, token SOS thô).
- [ ] `db:check-invariants` sạch sau seed.
