# API_SPEC (đề xuất)

> Plan không định nghĩa chi tiết endpoint. Đây là **đề xuất** bám theo F01–F15 và `BUSINESS_RULES.md`. Có thể điều chỉnh, nhưng giữ đúng quy tắc quyền và mã lỗi.

Quy ước: prefix `/api`, JSON, auth `Authorization: Bearer <sanctum token>` (trừ nhóm Public). Lỗi: `{ "error": "<code>", "message": "..." }`. Phân trang feed/danh sách: **cursor**.
Mã lỗi: `401` chưa đăng nhập · `403` không đủ quyền (vd `women_only`, chưa verify) · `404` · `409` xung đột trạng thái · `422` validate · `429` rate limit.

## 1. Public
| Method | Path | Mô tả |
|---|---|---|
| POST | `/auth/otp/request` | Gửi OTP email (3 lần/giờ) |
| POST | `/auth/otp/verify` | Xác thực OTP (5 sai/giờ) → token; user mới cần `birth_date`, consent `terms`+`privacy`; <18 ⇒ 422 |
| GET | `/track/{token}` | Trang HTML theo dõi SOS (không phải JSON, `noindex`) |
| GET | `/track/{token}/position` | JSON `{display_name, position, updated_at, live_status}`; rate limit; 404 nếu phiên kết thúc |

## 2. Hồ sơ & xác minh (F01–F03)
| Method | Path | Ghi chú |
|---|---|---|
| POST | `/auth/logout` | |
| GET/PATCH | `/me` | Không cho đổi `gender` sau verify; đổi avatar ⇒ reset `photo_verified_at` |
| PUT | `/me/emergency-contact` | SĐT mã hóa |
| POST/GET | `/me/consents` | Ghi bản ghi mới mỗi lần đồng ý/rút |
| POST | `/me/photo-verification/challenge` | Trả lệnh ngẫu nhiên (cần consent `biometric`; ≤5 thất bại/24h) |
| POST | `/me/photo-verification/submit` | Upload 2 ảnh → face service → xóa ảnh (`try/finally`) → set `photo_verified_at` |
| DELETE | `/me` | Xóa tài khoản = ẩn danh hóa |
| GET/POST/DELETE | `/me/blocks` | Quản lý block |
| GET/POST/DELETE | `/me/devices` | Đăng ký Expo push token |
| GET | `/users/{id}` | Hồ sơ công khai: tên, ảnh, verify, rating, thẻ, completion_rate; **không** thông tin nhạy cảm |

## 3. Danh mục (F04)
| GET | `/cinemas?lat&lng&radius&city` | Gần nhất (`$near`/`$geoNear`) |
| GET | `/movies?city&q` | |
| GET | `/showtimes?cinema_id&movie_id&date` | Bỏ `is_cancelled` |

## 4. Post / Feed / Join (F05–F09)
| Method | Path | Ghi chú |
|---|---|---|
| GET | `/posts` | Feed: lọc rạp/phim/suất/thành phố; ẩn block hai chiều; ẩn `women_only` với người không đủ điều kiện; lọc theo giới tính mong muốn; chỉ `open`; trả `join_count`, snapshot; cursor |
| POST | `/posts` | `StorePostRequest`: cần verify tuổi+ảnh; `women_only` chỉ nữ verified (403); `vibe_tags ≤5`; `max_join_requests 1–20`; kiểm tra chồng lấn |
| GET | `/posts/{id}` | |
| DELETE | `/posts/{id}` | Chủ bài hủy trước giờ chiếu |
| POST | `/posts/{id}/joins` | Join: 409 khi đầy/quá cutoff/trùng; rate limit; ≤10 pending |
| DELETE | `/joins/{id}` | Rút khi còn pending (`join_count--`) |
| GET | `/posts/{id}/joins` | **Chỉ chủ bài**; đã xếp hạng (Jaccard); loại user bị block |
| POST | `/posts/{id}/select/{join}` | Chọn người (5 lớp, xem BUSINESS_RULES §3) |
| POST | `/joins/{id}/cancel` | Hủy sau accepted (chủ bài hoặc người được chọn) |
| GET | `/me/joins`, `/me/posts` | Tab "Yêu cầu & Chat" |

## 5. Chat (F10)
| GET | `/chats` | Chat của mình |
| GET | `/chats/{id}/messages?cursor` | Policy: thành viên; lọc `expire_at > now` |
| POST | `/chats/{id}/messages` | Chỉ khi `active` và `now < read_only_at`; chỉ `text`; `type=system` bị cấm từ client |
| WS | Reverb private channel `chat.{id}` | |

## 6. SOS & vị trí (F11, F12)
| Method | Path | Ghi chú |
|---|---|---|
| POST | `/sos` | Tạo phiên: cần consent `location` + có liên hệ khẩn cấp; ≤3 phiên/24h; 1 phiên active (409). Trả `tracking_url` (token thô **chỉ trả lần này**) |
| PUT | `/sos/{id}/position` | App gửi vị trí mỗi 10–15s, **ghi đè** `live_position` |
| POST | `/sos/{id}/extend` | +1h, `extend_count ≤ 5`, tối đa 6h |
| POST | `/sos/{id}/end` | "Tôi an toàn" → `ended`, `user_safe`, xóa `live_position` |
| POST/PUT/DELETE | `/posts/{id}/location-share` | F12 (nên có): ≤2 viewer, ghi đè vị trí, tự hết hạn |

## 7. Rating & Report (F13, F14)
| POST | `/joins/{id}/ratings` | Sau suất chiếu; sao + ≤4 thẻ + nhận xét; ẩn |
| GET | `/joins/{id}/ratings` | Chỉ trả khi đã reveal |
| POST | `/reports` | `target_type`, `target_id`, `reason`, `detail`; tạo snapshot evidence; `hold_count++` |

## 8. Admin (`role:admin|moderator`, prefix `/api/admin`)
| GET | `/dashboard` | Thống kê từ timestamp/`daily_stats` |
| GET | `/reports`, `/reports/{id}` | Mở/xem evidence ⇒ `audit_logs` |
| POST | `/reports/{id}/actions` | `warn`/`suspend`/`ban`/`dismiss` ⇒ `moderation_actions` + `audit_logs`; `ban` ghi `banned_identifiers` |
| GET | `/users`, `/users/{id}` | Trạng thái |
| POST | `/users/{id}/lock`, `/unlock` | `audit_logs` |
| GET | `/sos`, `/sos/{id}` | Phiên đang hoạt động (vị trí cuối chỉ khi còn hiệu lực) |
| POST | `/sos/{id}/handled`, `/close` | `handled` / `admin_closed`; có thể tạo report từ SOS |
| POST | `/catalog/import-csv` | Dán CSV → báo created/duplicate/errors; ghi `sync_runs` + `audit_logs` |
| GET | `/sync-runs` | Lịch sử sync |
| GET | `/audit-logs` | Chỉ đọc (admin) |
