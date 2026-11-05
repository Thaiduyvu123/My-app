# PROMPTS — prompt mẫu để vibe code từng giai đoạn

Cách dùng: đặt cả thư mục này vào repo (`CLAUDE.md` ở gốc, `docs/` bên cạnh). Mở agent (Claude Code/Cursor) và dán lần lượt các prompt dưới đây. Mỗi prompt = một lượt, kiểm tra kết quả trước khi sang lượt sau.

## 0. Khởi động (dán đầu tiên)
```
Đọc CLAUDE.md và docs/ARCHITECTURE.md. Tóm tắt lại cho tôi trong 10 dòng: dự án làm gì, stack, 5 quy ước quan trọng nhất,
và những thứ KHÔNG được làm. Chưa viết code. Nếu có điểm mâu thuẫn hoặc thiếu thông tin, liệt kê để tôi chốt.
```

## 1. Dựng khung
```
Theo docs/ARCHITECTURE.md mục 2, tạo cấu trúc monorepo (backend Laravel, mobile Expo TypeScript, admin Vite React TS, face-service Node).
Cấu hình backend dùng mongodb/laravel-mongodb, Sanctum, và hướng dẫn chạy MongoDB replica set local.
Viết một test xác nhận: (1) transaction chạy được, (2) Sanctum tạo token với tokenable_id là ObjectId,
(3) $inc lồng nhau rating.tag_counts.punctual hoạt động. Báo kết quả thật.
```

## 2. Model & migration (lặp lại cho từng nhóm collection)
```
Đọc docs/DATABASE.md mục 3.1, 3.8, 3.9 và mục 5. Tạo model, migration (index), factory cho: users, consents, blocks, otp_codes, banned_identifiers.
Dùng $fillable chặt, cấm mass-assign các field trong CLAUDE.md mục 4. Field đánh dấu "(suy luận)" — liệt kê lại những chỗ bạn phải đoán để tôi duyệt.
```
Nhóm tiếp theo: `posts + join_requests` → `chats + messages` → `ratings + reports + moderation_actions` → `sos_alerts + location_shares` → `audit_logs + sync_runs + daily_stats` → `movies + cinemas + showtimes`.

## 3. Auth & hồ sơ (F01–F02)
```
Làm F01 theo docs/PRD.md và docs/BUSINESS_RULES.md §11 và docs/API_SPEC.md §1–2: OTP email (lưu hash, hết hạn 10 phút, rate limit 5 sai/giờ, 3 gửi lại/giờ),
đăng ký có birth_date chặn <18, consent terms+privacy. Viết FormRequest, Policy nếu cần, và test cho: <18 bị 422, OTP sai 5 lần bị 429, mã hết hạn.
```

## 4. Photo Verification (F03)
```
Làm F03 theo BUSINESS_RULES §10 và SAFETY_PRIVACY §4: endpoint challenge/submit, face-service Node (face-api.js) so khớp avatar với 2 ảnh theo lệnh ngẫu nhiên.
Bắt buộc xóa ảnh/descriptor trong finally. Giới hạn 5 thất bại/24h. Test: ảnh bị xóa cả khi face-service lỗi.
```

## 5. Feed, đăng bài, join (F05–F07)
```
Làm F05–F07 theo BUSINESS_RULES §2 và API_SPEC §4. Denormalize host/movie/cinema snapshot vào posts. Index feed theo ESR, phân trang cursor.
StorePostRequest: women_only chỉ nữ đã verify (403). Join: 409 khi đầy/quá cutoff/trùng giờ, cập nhật join_count bằng $inc.
Feed chỉ trả join_count, không trả danh sách. Seed 100 user và 25 bài sau khi index xong. Viết test cho mọi nhánh 403/409.
```

## 6. Xếp hạng & chọn người (F08–F09) — phần khó nhất
```
Làm RankingService (Jaccard 0.70 / rating 0.15 / cold-start 0.15, tính lúc đọc) và SelectJoinService theo BUSINESS_RULES §3 và §5 (5 lớp phòng thủ).
Viết test đua: hai request chọn đồng thời, cả khi bật và tắt transaction. Giải thích cho tôi từng lớp bảo vệ nằm ở dòng code nào.
```

## 7. Chat, report (F10, F13)
```
Làm chat Reverb và report theo BUSINESS_RULES §7–8. Policy kiểm tra read_only_at/expire_at trực tiếp. hold_count khi có report, snapshot evidence ≤50 tin.
Test: hai report cùng lúc, đóng lần lượt, chỉ khôi phục expire_at khi hold_count về 0.
```

## 8. SOS (F11) & `/track`
```
Làm SOS theo SAFETY_PRIVACY §2 và API_SPEC §6 + sos_alerts ở DATABASE §3.11. Token 32 byte, DB chỉ lưu SHA-256, trả token thô một lần.
Trang /track/{token} (blade + Leaflet, polling 10s, noindex, rate limit) chỉ lộ tên hiển thị + vị trí + thời điểm.
Job sos:expire-live. KHÔNG báo người đi cùng, KHÔNG gửi SMS tự động. Test: gia hạn tối đa 6h, kết thúc xóa live_position.
```

## 9. Rating (F14)
```
Làm RatingService theo BUSINESS_RULES §6: ẩn đến khi đủ cặp hoặc 72h, reveal idempotent (update điều kiện revealed_at null rồi mới $inc).
Test TC10–12: tạo rating không đổi điểm, reveal hai lần không cộng đôi, API không lộ rating chưa reveal.
```

## 10. Admin
```
Làm Admin React theo docs/SCREENS.md mục 2 và docs/DESIGN_SYSTEM.md mục 6, gọi API_SPEC §8. Mọi thao tác xem evidence/xử lý ghi audit_logs.
Bỏ các chi tiết sai lệch được liệt kê ở SCREENS §3 (CCCD, gọi công an, SMS tự động...).
```

## 11. Mobile UI (lặp theo từng màn)
```
Dựng màn "Khám phá (Feed)" cho Expo theo docs/ui-reference/07-feed.png, docs/DESIGN_SYSTEM.md, và các sửa đổi ở docs/SCREENS.md (chỉ hiện join_count, bỏ "Còn 12 vé").
Dùng token theme, có trạng thái loading/empty/error. Không copy HTML/Tailwind.
```

## 12. Kiểm tra cuối mỗi tính năng (dùng lại nhiều lần)
```
Rà lại tính năng vừa làm theo "Definition of Done" trong CLAUDE.md mục 8. Chạy test và db:check-invariants, báo kết quả thật.
Liệt kê mọi chỗ code có thể rò dữ liệu nhạy cảm hoặc vi phạm mục "KHÔNG làm". Chưa sửa gì nếu tôi chưa duyệt.
```

## Mẹo
- Một prompt một tính năng; đừng gộp nhiều tuần.
- Khi agent "đoán", yêu cầu nó ghi vào `docs/DECISIONS.md`.
- Dán ảnh UI vào prompt khi làm màn hình; luôn nhắc đọc `docs/SCREENS.md` để tránh làm theo phần UI sai lệch.
