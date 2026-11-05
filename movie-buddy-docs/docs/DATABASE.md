# DATABASE — MongoDB (v10, 24 collection)

> **Nguồn:** hợp nhất `Thiết kế Database v10` (phần mới/thay đổi) và các quy tắc trong Project Plan v10.
> **Lưu ý:** file DB v9 gốc ("file 02") không nằm trong bộ tài liệu được cung cấp. Các field đánh dấu **(suy luận)** được tái dựng từ Plan v10 để đủ chạy — hãy đối chiếu với file 02 và sửa nếu khác. Field không đánh dấu là lấy trực tiếp từ tài liệu.

## 1. Quy ước
- `snake_case` tiếng Anh; `_id` ObjectId; thời gian UTC hậu tố `_at`; trạng thái luôn là `status`; enum tiếng Anh không dấu.
- Kiểu: `oid`, `str`, `int`, `num`, `bool`, `date`, `geo` (GeoJSON Point), `enum`, `arr(...)`, `obj`. BB = bắt buộc (Y/N/C = có điều kiện).
- Field suy ra **không lưu**: `rating_avg = rating.sum / rating.count`; `completion_rate = completed_count / accepted_count`; "điểm tin cậy" (tính ở app từ hai giá trị trên); online = `last_active_at` trong 2 phút.

## 2. Danh sách collection
**Lõi (9):** `users`, `posts`, `join_requests`, `chats`, `messages`, `ratings`, `reports`, `blocks`, `consents`
**Danh mục (3):** `movies`, `cinemas`, `showtimes`
**An toàn/vận hành (12):** `moderation_actions`, `banned_identifiers`, `otp_codes`, `verification_attempts`, `device_tokens`, `notifications`, `location_shares`, `sos_alerts`, `personal_access_tokens`, `audit_logs`, `sync_runs`, `daily_stats` (tùy chọn)

## 3. Chi tiết collection

### 3.1 users
| Field | Kiểu | BB | Mô tả |
|---|---|---|---|
| `email` | str | Y | Unique. Chuẩn hóa trước khi so/ban |
| `display_name` | str | Y | |
| `birth_date` | date | Y | Server tính tuổi, chặn <18 **(suy luận tên field)** |
| `gender` | enum | Y | `male`, `female` (nhị phân là phạm vi MVP). **Khóa sau verify** |
| `seeking_gender` | enum | N | Giới tính mong muốn **(suy luận)** |
| `favorite_genres` | arr(str) | N | `genre_code`, dùng cho Jaccard **(suy luận tên)** |
| `avatar_url` | str | N | Đổi avatar ⇒ reset `photo_verified_at` |
| `photo_verified_at` | date | N | Có = đã Photo Verified |
| `age_verified_at` | date | N | Tự khai + điều khoản |
| `age_verify_method` | enum | N | `self_declared` (cho phép nâng cấp eKYC sau) |
| `role` | enum | Y | `user`, `moderator`, `admin` **(suy luận)** |
| `status` | enum | Y | `active`, `suspended`, `banned` (theo quy ước "mọi trạng thái là `status`") **(suy luận giá trị)** |
| `city_code` | str | N | |
| `favorite_cinema_ids` | arr(oid) | N | ≤5, không trùng; gợi ý lọc feed |
| `seeking_age_range` | obj | N | `{min,max}` 18–99, `min ≤ max`. **Mặc định null, tùy chọn, mặc định tắt** |
| `emergency_contact` | obj | N | `{name, phone (mã hóa), relationship ≤30 ký tự}` |
| `rating` | obj | Y | `{sum, count, tag_counts:{punctual,respectful,shared_taste,no_spoiler,fun_company}}` mặc định 0; **chỉ cộng khi rating đã reveal** |
| `stats` | obj | Y | `{completed_count, accepted_count, cancel_after_accept_count, sos_count}` mặc định 0 |
| `last_active_at` | date | N | |
| `deleted_at` | date | N | Xóa tài khoản = ẩn danh hóa PII |

**Cấm mass-assign:** `rating.*`, `stats.*`, `photo_verified_at`, `age_verified_at`, `role`, `status`.

### 3.2 posts
| Field | Kiểu | BB | Mô tả |
|---|---|---|---|
| `host_id` | oid | Y | |
| `showtime_id`, `movie_id`, `cinema_id` | oid | Y | |
| `city_code` | str | Y | |
| `start_time`, `end_time` | date | Y | Snapshot từ showtime (dùng kiểm tra chồng lấn) |
| `note` | str | N | |
| `audience` | enum | Y | `all`, `women_only` (+ giá trị khác theo file 02) |
| `status` | enum | Y | `open`, `matched`, `closed`, `cancelled`, `expired` |
| `expires_at` | date | Y | = giờ chiếu |
| `join_cutoff_at` | date | Y | = `start_time − 2h` |
| `join_count` | int | Y | Số join `pending`, mặc định 0 |
| `max_join_requests` | int | Y | Mặc định 10, 1–20 |
| `cost_split` | enum | N | `split_equal`, `host_pays`, `self_pay`, `flexible` |
| `vibe_tags` | arr(enum) | N | ≤5: `no_spoiler`, `deep_discuss`, `middle_row`, `popcorn`, `quiet` |
| `host_age`, `age_range` | int, obj | C | **P2, tùy chọn**: chỉ khi bật lọc theo tuổi |
| `selected_join_id` | oid | N | **(suy luận)** |
| `host_snapshot` | obj | Y | Denormalize cho feed: `{display_name, avatar_url, photo_verified, rating_avg, rating_count}` — tránh `$lookup` **(suy luận chi tiết)** |
| `movie_snapshot`, `cinema_snapshot` | obj | Y | `{title, poster_url, format}` / `{name, address}` **(suy luận chi tiết)** |
| `created_at`, `matched_at`, `closed_at`, `cancelled_at` | date | | Timestamp từng bước để thống kê |

Feed **chỉ hiện con số** `join_count`, không lộ danh sách người join.

### 3.3 join_requests
`post_id`, `user_id`, `message` (≤300 ký tự), `status` (`pending`, `accepted`, `declined`, `withdrawn`, `expired`, `cancelled`), `rank.score` (tùy chọn `rank.breakdown`), `created_at`, `accepted_at`, `cancelled_at`, `cancelled_by`. **Unique `{post_id, user_id}`**. *(Chi tiết field: suy luận.)*

### 3.4 chats
`post_id`, `join_request_id`, `members` (arr(oid), 2 người), `status` (`active`, `read_only`), `read_only_at` (= `end_time + 24h`), `expire_at` (= `end_time + 3 ngày`; **null** khi có report mở), `hold_count` (int, mặc định 0), `created_at`.
**Unique `{join_request_id}` và `{post_id}`** (lớp phòng thủ chống tạo chat thứ hai).

### 3.5 messages
| Field | Kiểu | BB | Mô tả |
|---|---|---|---|
| `chat_id` | oid | Y | |
| `sender_id` | oid | C | `null` khi `type = system` |
| `type` | enum | Y | `text` (mặc định), `system` |
| `system_code` | enum | N | `matched`, `read_only_soon`, `read_only`, `deleting_soon` |
| `body` | str | Y | Chỉ văn bản. Không ảnh |
| `created_at`, `expire_at` | date | Y | TTL theo `expire_at` (tin `system` cũng có) |

Tin `system` do server tạo; Policy không cho client tạo.

### 3.6 ratings
`join_request_id` (hoặc `post_id`), `rater_id`, `ratee_id`, `stars` (1–5), `comment` (≤300, tùy chọn), `tags` (arr(enum) ≤4, không trùng, **chỉ thẻ tích cực**: `punctual`, `respectful`, `shared_taste`, `no_spoiler`, `fun_company`), `revealed_at` (null = đang ẩn), `created_at`. Unique `{join_request_id, rater_id}`. Khi reveal: `$inc rating.sum`, `rating.count`, `rating.tag_counts.<tag>` — **idempotent** (điều kiện `revealed_at == null`).

### 3.7 reports
`reporter_id`, `target_type` (`user`, `post`, `join_request`), `target_id`, `reason` (có `gender_fraud`, ... — xem UI "Báo cáo vi phạm"), `detail` (≤500), `evidence` (snapshot ≤50 tin nhắn, chỉ văn bản), `status` (`open`, `resolved`, `dismissed`), `chat_id`, `created_at`, `resolved_at`. **Unique partial `{reporter_id, target_type, target_id}`** chống spam. Ngưỡng ưu tiên: đếm **distinct reporter** của người bị báo cáo → đạt ngưỡng thì vào hàng chờ ưu tiên (**không tự khóa**).

### 3.8 blocks
`blocker_id`, `blocked_id`, `created_at`. **Unique `{blocker_id, blocked_id}`.** Áp **hai chiều** ở feed, danh sách join, join, chat. (Thay cho mảng `blockedUsers` trong user.)

### 3.9 consents
`user_id`, `type` (`terms`, `privacy`, `biometric`, `location`, `marketing` tùy chọn), `version`, `granted` (bool), `created_at`. Mỗi lần đồng ý/rút lại = **một bản ghi mới**. `marketing` không được chặn dịch vụ; nếu không làm khuyến mãi thì bỏ checkbox.

### 3.10 movies / cinemas / showtimes
- `movies`: `title`, `title_slug` (unique, dùng upsert), `genres` (arr `genre_code`), `duration_min`, `poster_url`.
- `cinemas`: `name`, `slug`, `city_code`, `address`, `location` (**geo, index `2dsphere`**), `amenities` (arr(str) ≤8: `sweetbox`, `gold_class`, `atmos`, `parking`...; nhãn ở lớp i18n).
- `showtimes`: `movie_id`, `cinema_id`, `start_time`, `end_time` (= start + duration_min), `format` (enum: `2d`, `3d`, `imax`, `other`...), `hall_name` (str, N), `is_cancelled` (bool). **Unique `{movie_id, cinema_id, start_time, format}`.** **Không** lưu số ghế/vé còn trống.

### 3.11 sos_alerts (thiết kế lại cho phiên trực tiếp)
| Field | Kiểu | BB | Mô tả |
|---|---|---|---|
| `user_id` | oid | Y | |
| `post_id` | oid | N | Có khi SOS trong buổi hẹn |
| `trigger_type` | enum | Y | `manual` |
| `position`, `position_accuracy` | geo, num | Y, N | Vị trí lúc bấm |
| `position_is_stale` | bool | Y | true nếu dùng vị trí cuối cũ |
| `status` | enum | Y | `new`, `handled` (việc xử lý của admin) |
| `handled_by`, `handled_at` | oid, date | N | Chỉ admin đặt |
| `live_status` | enum | Y | `active`, `ended` (độc lập với `status`) |
| `live_until` | date | Y | Mặc định `now + 2h`; kiểm tra lazy |
| `live_position`, `live_position_at` | geo, date | N | **Ghi đè**, không lịch sử; xóa khi kết thúc |
| `share_token_hash` | str | Y | SHA-256; không lưu token thô |
| `extend_count` | int | Y | Mặc định 0, tối đa 5 (tổng ≤6h) |
| `ended_at`, `end_reason` | date, enum | N | `user_safe`, `expired`, `admin_closed` |
| `purge_at` | date | N | Đặt khi `live_status=ended` **và** `status=handled`: `max(ended_at, handled_at) + 30 ngày` |

**Index:** unique `{share_token_hash}`; `{user_id, created_at:-1}`; `{live_status, live_until}`; `{status, created_at:-1}`; partial `{purge_at}` khi `!= null`.
**Không lưu:** pin, WiFi, mã thiết bị, lịch sử đường đi.

### 3.12 location_shares (F12)
`user_id`, `post_id`, `viewer_ids` (≤2), `position` (geo, **ghi đè**), `updated_at`, `status`, `expire_at` (= `end_time + 2h`, TTL).

### 3.13 moderation_actions
`report_id`, `target_user_id`, `actor_id`, `action` (`warn`, `suspend`, `ban`, `dismiss`), `note`, `created_at`. Kiểm duyệt 4 cấp: xem → warn → suspend → ban.

### 3.14 banned_identifiers
`type` (`email_hmac`), `hmac_hash` (HMAC của email **đã chuẩn hóa**: bỏ `+alias`, dấu chấm Gmail), `reason`, `created_at`. **Không** lưu hash khuôn mặt. Chỉ là rào cản, có thể bị lách — ghi rõ trong báo cáo.

### 3.15 otp_codes / verification_attempts / device_tokens / notifications / personal_access_tokens
- `otp_codes`: `email`, `code_hash` (không lưu mã thô), `attempts`, `expire_at` (+10 phút, TTL).
- `verification_attempts`: `user_id`, `result`, `challenge` (lệnh ngẫu nhiên), `created_at`, TTL 30 ngày. Tối đa 5 thất bại/24h.
- `device_tokens`: `user_id`, `expo_token`, `device_name`, `last_used_at`.
- `notifications`: `user_id`, `type`, `payload`, `read_at`, TTL 30 ngày (tùy chọn cắt).
- `personal_access_tokens`: do Sanctum quản lý (`tokenable_id` là ObjectId).

### 3.16 audit_logs (mới)
`actor_id`, `actor_role` (`moderator`, `admin`, `system`), `action` (`view_report`, `view_evidence`, `resolve_report`, `moderation_action`, `sos_handled`, `sos_closed`, `user_lock`, `user_unlock`, `csv_import`, `config_change`), `target_type` (`user`, `report`, `sos_alert`, `showtime`, `system`), `target_id`, `meta` (≤1KB, **không PII, không nội dung tin nhắn**), `created_at`, `expire_at` (+12 tháng).
Index: `{actor_id, created_at:-1}`, `{target_type, target_id, created_at:-1}`, TTL `{expire_at}`. **Chỉ thêm mới** — model không có hàm update/delete. (Tùy chọn P2: `prev_hash`/`hash` nối chuỗi.)

### 3.17 sync_runs (mới)
`source` (`sheet`, `admin_csv`), `triggered_by` (oid|null = Scheduler), `status` (`running`, `success`, `partial`, `failed`), `started_at`, `finished_at`, `counts` `{created, updated, skipped, errors, cancelled_showtimes}`, `error_samples` (≤20 `{row, message}`), `expire_at` (+90 ngày, TTL). Index `{started_at:-1}`.

### 3.18 daily_stats (mới, tùy chọn)
`date` (str `YYYY-MM-DD`, giờ VN, unique), `posts_created`, `posts_matched`, `joins_created`, `joins_accepted`, `cancels_after_accept`, `reports_created`, `reports_resolved`, `sos_started`, `ratings_revealed`, `avg_minutes_to_match`, `avg_minutes_to_resolve`. Không chứa PII; giữ 24 tháng.

## 4. Enum tổng hợp
| Enum | Giá trị |
|---|---|
| post.status | `open`, `matched`, `closed`, `cancelled`, `expired` |
| join.status | `pending`, `accepted`, `declined`, `withdrawn`, `expired`, `cancelled` |
| chat.status | `active`, `read_only` |
| post.cost_split | `split_equal`, `host_pays`, `self_pay`, `flexible` |
| post.vibe_tag | `no_spoiler`, `deep_discuss`, `middle_row`, `popcorn`, `quiet` |
| rating.tag | `punctual`, `respectful`, `shared_taste`, `no_spoiler`, `fun_company` |
| message.type / system_code | `text`,`system` / `matched`,`read_only_soon`,`read_only`,`deleting_soon` |
| consent.type | `terms`, `privacy`, `biometric`, `location`, `marketing` |
| sos.trigger_type / live_status / end_reason | `manual` / `active`,`ended` / `user_safe`,`expired`,`admin_closed` |
| audit.action / target_type | xem 3.16 |
| sync.source / status | `sheet`,`admin_csv` / `running`,`success`,`partial`,`failed` |
| report.target_type | `user`, `post`, `join_request` |

## 5. Index quan trọng (tóm tắt)
- `join_requests`: unique `{post_id, user_id}`; `{user_id, status}` (đếm ≤10 pending).
- `chats`: unique `{join_request_id}`, unique `{post_id}`; TTL/partial theo `expire_at`.
- `reports`: unique partial `{reporter_id, target_type, target_id}`.
- `blocks`: unique `{blocker_id, blocked_id}`; `{blocked_id}`.
- `posts` (feed): index theo **ESR** (Equality → Sort → Range), ví dụ `{status, city_code, cinema_id, start_time}`, phân trang **con trỏ**. Field mới (`join_count`, `cost_split`, `vibe_tags`) không cần index.
- `cinemas`: `2dsphere` trên `location`.
- `showtimes`: unique `{movie_id, cinema_id, start_time, format}`.
- Xem thêm mục 3.11, 3.16, 3.17 cho `sos_alerts`, `audit_logs`, `sync_runs`.

## 6. Retention (đưa nguyên vào điều khoản + màn consent)
| Dữ liệu | Thời hạn |
|---|---|
| Selfie, descriptor | Xóa ngay sau xử lý |
| `otp_codes` | 10 phút |
| `verification_attempts`, `notifications` | 30 ngày |
| `messages` | `end_time + 3 ngày` (giữ nếu có report mở) |
| `reports.evidence`, `sos_alerts` | Xử lý xong + 30 ngày |
| `location_shares` | `end_time + 2 giờ` |
| `sos_alerts`: vị trí trực tiếp | Xóa ngay khi phiên kết thúc; `position` lúc bấm giữ đến `purge_at` |
| `audit_logs` | 12 tháng (TTL) |
| `sync_runs` | 90 ngày (TTL) |
| `daily_stats` | 24 tháng, không PII |
| `posts`, `join_requests`, metadata chat | Đề xuất 12 tháng (cần chốt) |
| `moderation_actions`, `banned_identifiers` | Dài hạn |

## 7. Invariants (`php artisan db:check-invariants` phải kiểm tra)
1. `posts.join_count` = số `join_requests` `pending` của post; `join_count ≤ max_join_requests`.
2. `posts.status = matched` ⇒ `join_count = 0`.
3. `sos_alerts.live_status = active` ⇒ `now < live_until` (hoặc job sắp chuyển `ended`); `ended` ⇒ `live_position` null.
4. Mỗi user có tối đa 1 `sos_alerts` `active`.
5. `purge_at != null` ⇒ `live_status = ended` và `status = handled`.
6. `messages.type = text` ⇒ `sender_id ∈ chats.members`; `type = system` ⇒ `sender_id = null`.
7. `ratings.tags` không trùng, tối đa 4.
8. `users.rating.tag_counts` = tổng thẻ trong rating đã reveal có `ratee_id` = user đó.
9. Mỗi post có tối đa 1 chat; mỗi join `accepted` có tối đa 1 chat.
10. `users.rating.sum/count` chỉ gồm rating đã reveal.
11. Mỗi `(post_id, user_id)` có tối đa 1 join.
12. Không có 2 post `matched`/join `accepted` của cùng một người chồng khoảng `[start, end)`.

## 8. Migration & seed
- Chưa có dữ liệu thật: chỉ cần Model + Migration index + Seeder.
- Mặc định field mới: `join_count=0`, `max_join_requests=10`, `stats.accepted_count=0`, `stats.sos_count=0`, `rating.tag_counts={tất cả 0}`, `messages.type='text'`, `sos_alerts.extend_count=0`.
- Seed demo **sau khi** index xong: 100 user giả (nhiều tổ hợp rạp/giờ/thể loại), 20–30 bài open, vài post matched/chat/rating để demo.
- Sau seed chạy `db:check-invariants`.

## 9. Điều KHÔNG thêm vào DB
CCCD · đăng nhập Google/Apple · 2FA/IP phiên · pin/WiFi/thiết bị trong SOS · ảnh chụp màn hình bằng chứng · số vé còn trống · "điểm tin cậy" · toggle "xóa lịch sử vị trí" · SMS tự động/`sms_logs`.
