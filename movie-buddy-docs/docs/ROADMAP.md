# ROADMAP — 10 tuần (checklist cho vibe coding)

> Làm theo thứ tự. Tick `[x]` khi xong **và** test/invariants đã chạy. Mô hình đăng bài/join phức tạp hơn matching tự động, nên giữ thời gian đệm cho tuần 5–6.

## Tuần 1 — Nền móng
- [ ] Chốt tên field + enum (đối chiếu `DATABASE.md`)
- [ ] Dựng monorepo (`backend/`, `mobile/`, `admin/`, `face-service/`)
- [ ] Laravel + MongoDB **replica set** chạy được; thử transaction
- [ ] **Kiểm tra Sanctum + MongoDB** (`tokenable_id` ObjectId)
- [ ] **Kiểm tra `$inc` lồng nhau** (`rating.tag_counts.<tag>`)
- [ ] Theme Cyber Twilight → React Native (`mobile/src/theme`)
- [ ] Màn consent + xác minh 18+ (khung)

## Tuần 2 — F01, F02
- [ ] Models/migrations: `users`, `consents`, `otp_codes`, `banned_identifiers`
- [ ] OTP email (hash, hết hạn 10 phút, rate limit 5/3 mỗi giờ), chặn <18
- [ ] Hồ sơ: ảnh, giới tính (khóa sau verify), thể loại yêu thích, liên hệ khẩn cấp (mã hóa)

## Tuần 3 — F03, F04
- [ ] Face service + `PhotoVerificationController` (`try/finally` xóa ảnh), `verification_attempts`
- [ ] Reset `photo_verified_at` khi đổi avatar (A2)
- [ ] `cinemas` 2dsphere + API rạp gần nhất + màn chọn rạp

## Tuần 4 — F05, F06, F07
- [ ] Denormalize snapshot vào `posts`; index feed (ESR); `blocks`
- [ ] Feed (lọc, block hai chiều, giới tính mong muốn, cursor)
- [ ] Tạo bài (`StorePostRequest`, `women_only` 403), Join (giới hạn, 409, `join_count`)
- [ ] Seeder demo **sau khi** index xong (100 user, 20–30 bài)

## Tuần 5 — F08, F09
- [ ] Danh sách join + Jaccard + cold-start (<2s)
- [ ] Chọn người 5 lớp (cả có và không có transaction) + test đua
- [ ] Hủy sau accept (chủ bài / người được chọn, trước/sau cutoff)

## Tuần 6 — F10, F13
- [ ] Chat Reverb; `read_only_at` lazy; TTL `messages`; tin `system`
- [ ] `hold_count` + snapshot evidence; Report/Block; rate limit; distinct reporter

## Tuần 7 — F15 + SOS nâng cấp
- [ ] Admin: duyệt report, `moderation_actions`, `audit_logs`, nhập CSV + `sync_runs`
- [ ] SOS: phiên trực tiếp, `/track/{token}`, gia hạn, "Tôi an toàn", job `sos:expire-live` (F11)

## Tuần 8 — F14, F12, thử máy thật
- [ ] Rating 2 chiều + reveal idempotent + thẻ nhanh (A1)
- [ ] Chia sẻ vị trí buổi hẹn (F12) nếu kịp
- [ ] Thử SOS trên máy thật Android + iOS

## Tuần 9 — Kiểm thử & triển khai
- [ ] Test TC01–TC43, `db:check-invariants`, retention jobs
- [ ] Deploy Oracle + Supervisor (Reverb), Atlas M0, CI/CD (GitHub Actions, Vercel)

## Tuần 10 — Hoàn thiện
- [ ] Báo cáo, slide, video demo (quay sẵn phòng khi hạ tầng lỗi)

## Cắt khi quá tải (theo thứ tự)
`notifications` → F12 → `daily_stats` + thống kê Admin → hash chain `audit_logs` → `rank.breakdown` → trọng số report → lọc theo tuổi → `amenities`/`hall_name` → checkbox `marketing` → liveness chỉ 1 ảnh. Nếu SOS nâng cấp không kịp: quay về F11 cũ (SMS một lần).

## KHÔNG cắt
A1–A8 (xem dưới), `blocks`, `moderation_actions`, `hold_count`, unique index `chats`.

| Mã | Lỗi cần tránh | Cách đúng |
|---|---|---|
| A1 | Điểm TB cập nhật khi tạo rating, lộ rating ẩn | Chỉ cộng khi reveal |
| A2 | Đổi avatar vẫn giữ huy hiệu | Reset `photo_verified_at` |
| A3 | `legalHold` boolean sai khi nhiều report | `hold_count` |
| A4 | Chống race 1 lớp | 5 lớp + unique index `chats` |
| A5 | Feed dùng `$lookup` | Denormalize snapshot vào `posts` |
| A6 | `blockedUsers` mảng trong user | Collection `blocks` |
| A7 | Ngưỡng report bị thao túng | Distinct reporter + unique partial |
| A8 | Giá trị logic bằng chuỗi tiếng Việt | Enum tiếng Anh snake_case |
