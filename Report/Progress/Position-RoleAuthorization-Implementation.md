# Báo cáo triển khai: Bổ sung Position (chức vụ) vào workflow phê duyệt

**Ngày lập:** 2026-07-20
**Phạm vi:** Bổ sung khái niệm `Position` để phân biệt thẩm quyền trong cùng `Role`, chuyển các bước duyệt hiện có từ "theo Role" sang "theo Role + Position". Không tạo bảng tổ chức mới, không đổi mô hình Department/Training Office, không đổi nghiệp vụ khác ngoài phần Position.

---

## 1. Thiết kế tổng quan

- `Position` là 1 backed enum (`app/Enums/Position.php`), không phải bảng riêng — đúng yêu cầu "ưu tiên Enum, không tạo bảng TrainingOffice/tổ chức mới".
- Mỗi `User` có thêm cột `position` (nullable, vì `teacher/student/admin` không có khái niệm chức vụ). `Position::forRole(string $role)` là nguồn duy nhất định nghĩa "role nào có những position nào" — dùng lại ở cả validation (Admin tạo/sửa user) và authorization (duyệt).
- Toàn bộ logic "ai được duyệt bước nào" tập trung vào **1 class duy nhất**: `app/Services/ApprovalAuthorityService.php`. Mọi FormRequest/Policy/Controller cần biết "user này có duyệt được không" đều gọi qua class này — không có nơi nào tự viết lại điều kiện `role + position` một lần nữa.
- Quyết định thiết kế đã thống nhất với người dùng trước khi code: vì chưa có dữ liệu production, **Position không dùng cơ chế "null = mặc định có toàn quyền"**. `position = null` ở các role có yêu cầu Position (`department_staff`, `training_office`, `leadership`) nghĩa là **không có quyền duyệt** — fail-closed. Factory/seeder được cập nhật để luôn gán Position hợp lệ, nhờ đó không phải sửa lại bất kỳ test cũ nào.

## 2. Toàn bộ file đã thay đổi

### File mới

| File | Lý do |
|---|---|
| `app/Enums/Position.php` | Enum `Position` + `label()` (hiển thị) + `forRole()` (map Role → Position hợp lệ, nguồn dữ liệu duy nhất). |
| `app/Services/ApprovalAuthorityService.php` | Tập trung toàn bộ logic "Role + Position nào được duyệt bước nào" — nơi duy nhất chứa `TRAINING_OFFICE_APPROVER_POSITIONS` (Head/Deputy Head) và `LEADERSHIP_APPROVER_POSITIONS` (Vice Principal/Principal). |
| `Modules/Training/database/migrations/2026_07_20_000000_add_position_to_users_table.php` | Thêm cột `position` vào `users` (theo đúng pattern của migration `add_avatar_path_to_users_table` đã có trong cùng module), kèm backfill nhẹ cho dữ liệu dev sẵn có. |
| `tests/Feature/ApprovalAuthorityServiceTest.php` | Test ma trận Role+Position cho 2 nhóm duyệt (Training Office, Leadership) + 1 test HTTP thật qua route duyệt batch tháng, chứng minh Training Staff bị 403 còn Training Head thì duyệt được. |
| `Report/Progress/Position-RoleAuthorization-Implementation.md` | Báo cáo này. |

### File sửa — lõi nghiệp vụ (Model, User, Factory, Seeder)

| File | Lý do |
|---|---|
| `app/Models/User.php` | Thêm `position` vào `$fillable` + cast `Position::class` (Laravel enum cast gốc, trả về `Position\|null`). |
| `database/factories/UserFactory.php` | Thêm `configure(): static` với `afterMaking()` tự gán Position mặc định ở tier cao nhất theo `role` cuối cùng (sau khi test override `['role' => ...]`) — để toàn bộ test hiện có (`ChangeRequestWorkflowTest`, `HolidayRescheduleWorkflowTest`, `TeachingSupportRequestWorkflowTest`, `InternalNotificationWorkflowTest`, `InitializeMonthlyScheduleTest`, `MonthlyAssignmentScopeResolverTest`, `AssignMonthlyScheduleSaveTest`) **không cần sửa** mà vẫn pass. |
| `Modules/Training/database/seeders/DemoUserSeeder.php` | Gán Position thật cho tài khoản demo (Hiệu trưởng/Phó hiệu trưởng, Trưởng/Phó phòng đào tạo, Trưởng khoa), thêm 1 tài khoản demo mới `pdt-3@demo.local` (Training Staff) để minh hoạ đúng ví dụ "Training Staff không tự duyệt được" trong yêu cầu; đồng thời backfill Position cho user demo đã tồn tại từ trước (vì `firstOrCreate` không update field khi email đã có). |

### File sửa — 5 điểm gate phê duyệt (Role → Role + Position)

| # | File | Trước | Sau |
|---|---|---|---|
| 1 | `Modules/Schedule/Application/ReviewChangeRequest/ReviewChangeRequestRequest.php` | `isTrainingOffice() \|\| isAdmin()` | `ApprovalAuthorityService::canApproveAsTrainingOffice()` (nhánh chung); nhánh `holiday_reschedule` vẫn chỉ Admin — không đổi, vì đây là quy tắc đặc biệt nằm ngoài mô hình Role+Position (PĐT/BGH không tham gia loại phiếu này theo thiết kế gốc). |
| 2 | `Modules/Schedule/Application/DepartmentMonthlyAssignmentBatch/DepartmentMonthlyAssignmentBatchReviewController.php` | `isTrainingOffice() \|\| isAdmin()` trong `authorizeReview()` | Constructor-inject `ApprovalAuthorityService`, gọi `canApproveAsTrainingOffice()`. |
| 3 | `Modules/Schedule/Application/TeachingSupportRequest/TeachingSupportRequestPolicy.php` | `process()`: `isAdmin() \|\| isTrainingOffice()` | Constructor-inject `ApprovalAuthorityService` (Policy đã đăng ký qua `Gate::policy()` trong `AppServiceProvider`, container tự resolve), `process()` gọi `canApproveAsTrainingOffice()`. |
| 4 | `Modules/Schedule/Application/TeachingSupportChangeRequest/TeachingSupportChangeRequestPolicy.php` | như trên | như trên |
| 5 | `Modules/Schedule/Application/LeadershipReviewSemesterPlan/LeadershipReviewSemesterPlanRequest.php` | `isLeadership() \|\| isAdmin()` | `ApprovalAuthorityService::canApproveAsLeadership()` |

Ở cả 5 điểm, hành vi Admin bypass giữ nguyên 100% (Admin luôn duyệt được, không phụ thuộc Position).

### File sửa — đồng bộ hiển thị nút duyệt (view-data, tránh hiện nút rồi 403)

| File | Thay đổi |
|---|---|
| `Modules/Schedule/resources/views/index.blade.php` | 2 biến `$canReviewWorkflow`/`$canLeadershipReview` đổi sang gọi `ApprovalAuthorityService` — đây là **choke point duy nhất** cho toàn bộ `_change-request-list.blade.php` và `_semester-plan.blade.php` (2 view đó chỉ đọc lại biến, không tự kiểm tra role), nên sửa 2 dòng này là đủ cho cả 2 slice. |
| `Modules/Schedule/Application/DepartmentMonthlyAssignmentBatch/DepartmentMonthlyAssignmentBatchController.php` | `'canReview'` (dòng hiển thị nút duyệt batch) đổi sang gọi `ApprovalAuthorityService`. |
| `Modules/Schedule/Application/TeachingSupportRequest/TeachingSupportRequestController.php` | `'canProcess'` đổi từ kiểm tra role thủ công sang `$request->user()?->can('process', $requestModel)` — nhất quán với `TeachingSupportChangeRequestController.php` (đã dùng `->can('process', ...)` sẵn từ trước, tự động ăn theo khi Policy đổi, không cần sửa file đó). |

### File sửa — Admin UI (gán/đổi Position)

| File | Thay đổi |
|---|---|
| `app/Http/Controllers/Admin/AdminController.php` | `store()`: thêm validate `position` (bắt buộc khi `Position::forRole($role)` khác rỗng, phải nằm trong danh sách hợp lệ của role đã chọn). Thêm `updatePosition(Request, User)`: action riêng, nhỏ, để đổi Position cho user đã tồn tại (hệ thống hiện chưa có "sửa user" nói chung, nên không mở rộng ngoài phạm vi Position). |
| `routes/web.php` | Thêm `POST admin/users/{user}/position` → `admin.users.update-position`. |
| `resources/views/admin/index.blade.php` | Form tạo user: thêm select Position, tự đổi danh sách option theo Role đã chọn (mở rộng script JS toggle đang dùng cho `department_id`). Bảng danh sách: thêm cột "Chức vụ" + form nhỏ đổi Position tại chỗ. |

## 3. Kiểm thử

- `tests/Feature/ApprovalAuthorityServiceTest.php` (mới): ma trận Role+Position cho `canApproveAsTrainingOffice`/`canApproveAsLeadership` (Head/Deputy Head/Vice Principal/Principal → true; Staff/null/role sai → false; Admin → true bất kể); 1 test HTTP thật qua `POST department-monthly-assignment-batches/{id}/approve` xác nhận Training Staff bị 403, Training Head duyệt thành công.
- Đã xác minh thủ công qua `php artisan tinker` (không qua DB) rằng `ApprovalAuthorityService` trả kết quả đúng và `TeachingSupportRequestPolicy` được container resolve đúng constructor mới (`Gate::policy()` inject `ApprovalAuthorityService` bình thường).
- Đã xác minh toàn bộ route liên quan (`change-request.review`, `department-monthly-assignment-batches.approve`, `schedule.leadership-review`, `admin.users.update-position`) đăng ký thành công qua `php artisan route:list`.
- Đã `php -l` toàn bộ file mới/sửa — không lỗi cú pháp.
- **Giới hạn môi trường:** không chạy được `php artisan test` đầy đủ trong sandbox này. `tests/TestCase.php` có `TestingDatabaseGuard` bắt buộc `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`; khi ép sqlite, migration có sẵn từ trước `2026_04_16_114010_remove_class_id_and_class_name_from_monthly_schedules_table.php` lỗi (`drop column "class_id"` vướng foreign key trên SQLite) — đã xác nhận lỗi này xảy ra **giống hệt** trên `ChangeRequestWorkflowTest` (test cũ, không đụng tới) nên đây là vấn đề môi trường có sẵn, không phải do thay đổi Position. `phpunit.xml` mặc định trỏ tới MySQL thật (`qlhl_cdhc_test` tại `127.0.0.1:3308`) nhưng guard chặn không cho chạy trực tiếp trên đó. Cần người dùng chạy `php artisan test` trên máy có kết nối MySQL/sqlite phù hợp để có kết quả pass/fail đầy đủ.

## 4. Workflow vẫn phụ thuộc hoàn toàn vào Role (chưa/ không áp Position)

| Workflow/điểm | Hiện trạng | Đánh giá |
|---|---|---|
| Phiếu điều chỉnh lịch nghỉ lễ/Tết (`change_type='holiday_reschedule'`) | Chỉ Admin duyệt, không qua Training Office/Leadership | Nằm ngoài mô hình Role+Position từ thiết kế gốc (không phải PĐT/BGH duyệt). Nếu sau này muốn đưa vào, cần quyết định nghiệp vụ trước (PĐT/BGH có tham gia hay không) — không tự ý đổi vì đây là quy tắc đặc biệt đã có chủ đích. |
| **Department Head** (`Position::DEPARTMENT_HEAD`) | Enum đã định nghĩa, Admin UI đã cho gán, nhưng **chưa được wire vào bất kỳ workflow duyệt nào** | Vì hiện tại không tồn tại bước "Department duyệt" nào trong code (khoa chỉ tạo phiếu / tự phân công GV hỗ trợ theo `department_id`, không có gate quyền duyệt cấp khoa). Thêm gate ở đây nghĩa là thêm 1 bước duyệt mới — nằm ngoài phạm vi "chỉ bổ sung Position, không đổi nghiệp vụ" nên **không triển khai**. Đây là gap rõ nhất khớp với ví dụ "Department Staff -> Department Head" trong yêu cầu — cần 1 slice duyệt mới (ví dụ `Modules/Schedule/Application/DepartmentReviewChangeRequest/`) nếu muốn hiện thực hoá bước này, nên làm thành hạng mục riêng. |
| `viewAny/view/create/confirm/withdraw` (2 Policy `TeachingSupportRequest`/`TeachingSupportChangeRequest`), `authorizeQueue/authorizeBatchView/authorizeDepartmentScope` | Vẫn dùng role + `department_id` thuần | Đây là quyền xem/tạo/thao tác nội bộ theo khoa, không phải "bước duyệt" — đúng phạm vi giữ nguyên, không cần Position. |

## 5. Rủi ro thiết kế cần lưu ý cho tương lai (không tự triển khai ngoài phạm vi)

- **`ApprovalAuthorityService` hiện dùng named method theo từng nhóm workflow** (`canApproveAsTrainingOffice`, `canApproveAsLeadership`) thay vì 1 method tổng quát `canApprove(User $user, string $role, array $positions)` nhận tham số. Chọn cách này vì hiện chỉ có 2 "họ" workflow cần Position (PĐT, BGH) — dùng named method để lời gọi ở FormRequest/Policy đọc rõ ràng, tránh over-engineer sớm. Nếu sau này có thêm nhiều cấp duyệt hơn (ví dụ Department Head, hoặc nhiều tổ hợp Role+Position khác), nên cân nhắc đổi sang method tổng quát để không phải thêm method mới mỗi lần có workflow mới.
- **`position = null` bị coi là không có quyền tuyệt đối**, kể cả khi role đó không thật sự cần phân biệt cấp bậc. Rủi ro vận hành: nếu Admin tạo nhầm 1 tài khoản `training_office`/`leadership` mà quên chọn Position (validation hiện đã chặn ở form tạo mới nên khó xảy ra), tài khoản đó sẽ không duyệt được gì cho tới khi có ai gán Position — không có "âm thầm có quyền" như trước, nhưng cũng không có cảnh báo chủ động (không có thông báo nhắc "tài khoản X thiếu Position"). Có thể cân nhắc thêm cảnh báo ở Admin UI (ví dụ badge "Thiếu chức vụ") nếu vấn đề này xuất hiện trong thực tế.
- **`Position::forRole()` gắn cứng bảng ánh xạ Role→Position trong 1 enum tĩnh.** Phù hợp với yêu cầu hiện tại (2-3 cấp/role, cố định), nhưng nếu tổ chức thực tế có nhiều cấp bậc hơn hoặc cần cấu hình theo từng đơn vị khác nhau, enum tĩnh sẽ không đủ linh hoạt — khi đó nên cân nhắc chuyển sang bảng cấu hình (không phải yêu cầu hiện tại, chỉ nêu để lưu ý nếu tổ chức mở rộng).
