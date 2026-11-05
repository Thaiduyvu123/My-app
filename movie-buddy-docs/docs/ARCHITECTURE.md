# ARCHITECTURE

## 1. Tổng quan
```
Mobile (RN/Expo) ─┐
Web (React/Vite) ─┼─► Laravel API (Sanctum, Policy, Reverb) ─► MongoDB (replica set)
Admin (React) ────┘            │
                               ├─► Face service (Node, face-api.js) — xử lý selfie tạm thời
                               ├─► Expo Push
                               ├─► Google Sheets (CSV công khai)
                               └─► Cloudflare R2 / Laravel Storage (avatar)
Người liên hệ SOS ──► /track/{token} (HTML tĩnh + Leaflet, polling 10s, noindex)
```
Không kết nối API rạp thật.

## 2. Cấu trúc repo đề xuất (monorepo)
```
movie-buddy/
├─ CLAUDE.md
├─ docs/                       # bộ tài liệu này
├─ backend/                    # Laravel
│  ├─ app/
│  │  ├─ Models/               # Eloquent-Mongo models
│  │  ├─ Http/
│  │  │  ├─ Controllers/Api/   # mỏng, gọi Service
│  │  │  ├─ Requests/          # FormRequest (StorePostRequest...)
│  │  │  └─ Middleware/        # role:admin|moderator
│  │  ├─ Policies/
│  │  ├─ Services/             # PostService, SelectJoinService, RatingService,
│  │  │                        # ChatHoldService, SosService, RankingService
│  │  ├─ Console/Commands/     # sync:showtimes, db:check-invariants, ...
│  │  ├─ Jobs/
│  │  └─ Enums/
│  ├─ database/{migrations,seeders}/
│  ├─ resources/views/track.blade.php    # trang /track
│  └─ tests/{Unit,Feature}/
├─ face-service/               # Node microservice
├─ mobile/                     # Expo app
│  └─ src/{theme,components,screens,navigation,api,hooks,store,i18n}/
├─ web/                        # React/Vite browser app
└─ admin/                      # React (Vite)
```
Controller mỏng; logic nghiệp vụ ở `Services`; rule truy cập ở `Policies`; validate ở `Requests`.

**Trạng thái hiện tại:** API Laravel có luồng OTP F01, Laravel MongoDB driver và cấu hình Atlas. SQLite chỉ dùng cho test/local không có Atlas; MongoDB mode dùng model ObjectId và model Sanctum token riêng. URI Atlas và password phải đặt riêng trong `backend/.env`. Kết nối Atlas thật và round-trip `tokenable_id` bằng ObjectId chỉ có thể kiểm tra sau khi cấu hình mật khẩu và allowlist IP.

## 3. Tech stack chi tiết
| Thành phần | Công nghệ | Ghi chú |
|---|---|---|
| Mobile | React Native (Expo) | Theme Cyber Twilight → `mobile/src/theme` |
| Web | React + Vite | Giao diện trình duyệt; dùng chung API và quy tắc nghiệp vụ với mobile |
| Backend | Laravel REST | |
| DB | MongoDB, `mongodb/laravel-mongodb` | Replica set để dùng transaction (Atlas có sẵn; local `--replSet` + `rs.initiate()`) |
| Auth | Sanctum | **Kiểm tra tương thích `tokenable_id` ObjectId ngay tuần 1–2** |
| OTP | Laravel Mail | Lưu hash, không lưu mã thô |
| Realtime | Laravel Reverb | Tiến trình chạy liên tục: cần VM + Supervisor, không chạy trên nền tảng "sleep" |
| Bản đồ | OSM + Leaflet | Google Maps chỉ dự phòng (tính phí) |
| Photo Verify | face-api.js qua Node, hoặc AWS Rekognition/Azure Face free tier | ML Kit chỉ phát hiện mặt, không so khớp |
| Storage | Cloudflare R2 (10GB free) hoặc Laravel Storage | |
| Push | Expo Push | |
| Admin | ReactJS gọi REST | Vercel/Netlify |
| Trang SOS | `/track/{token}` | Công khai, chỉ đọc, `noindex`, rate limit |

## 4. Triển khai
- **Local:** XAMPP (Apache + PHP) + MongoDB replica set. Demo qua LAN hoặc ngrok/Cloudflare Tunnel.
- **Production:** Laravel + Reverb trên Oracle Cloud Free Tier (VM ARM) + Supervisor; MongoDB Atlas M0; Admin trên Vercel/Netlify. Railway/Render không còn free vĩnh viễn — kiểm tra giá trước khi chốt.
- **CI/CD:** push GitHub → GitHub Actions chạy PHPUnit + Jest → backend pass thì deploy Oracle qua SSH + restart Reverb (Supervisor); admin pass thì Vercel tự deploy; mobile build bằng EAS Build khi cần demo.
- **Dự phòng:** Oracle hết ARM/thu hồi VM → chạy local + Cloudflare Tunnel; quay sẵn video demo.

## 5. Scheduled jobs / commands
| Command | Chu kỳ | Việc |
|---|---|---|
| `sync:showtimes` | hằng giờ | Nạp Sheet → movies/cinemas/showtimes |
| `posts:expire` | 5–10 phút | Post quá `expires_at` chưa chọn → `expired`; join pending → `expired` |
| `sos:expire-live` | 5 phút | `live_status=active` & `live_until<now` → `ended`, `end_reason=expired`, xóa `live_position` |
| `stats:daily` | hằng giờ | Cập nhật `daily_stats` (tùy chọn) |
| `abuse:scan` | hằng ngày | Quét tài khoản mới có tỷ lệ join/bị từ chối cao, đổi thông tin sớm |
| `archive:purge` | hằng tuần | Dọn dữ liệu quá retention (gồm `daily_stats` 24 tháng) |
| `db:check-invariants` | thủ công/CI | Kiểm tra invariants |

TTL index MongoDB lo: `messages`, `otp_codes`, `verification_attempts`, `notifications`, `audit_logs`, `sync_runs`, `location_shares`.

## 6. Bảo mật & vận hành
- Rate limit: OTP (5 sai/giờ, 3 gửi lại/giờ), report/ngày, join/ngày, `/track` theo IP.
- Token SOS: 32 byte ngẫu nhiên, DB chỉ lưu SHA-256.
- SĐT liên hệ khẩn cấp: mã hóa ở tầng ứng dụng (Laravel `Crypt`/cast encrypted).
- Admin: route riêng, middleware `role:admin|moderator`, mọi thao tác nhạy cảm ghi `audit_logs`.
- Xóa selfie bằng `try/finally` trong luồng verify.
