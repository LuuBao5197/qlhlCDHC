# Đối Chiếu Database Với Quy Trình Điều Hành Huấn Luyện

## 1. Mục tiêu

Tài liệu này đối chiếu database hiện tại của hệ thống với quy trình điều hành huấn luyện và các use case đã mô tả trong:

- `Report/Diagrams/Usecase/StaffDTUseCase.puml`
- `Report/Diagrams/Usecase/StaffOfDepartmentUseCase.puml`
- `Report/Diagrams/Usecase/VicePricipalUseCase.puml`
- `Report/Diagrams/Usecase/TeacherUseCase.puml`
- `Report/Diagrams/Usecase/AdminUseCase.puml`

Mục tiêu là trả lời ba câu hỏi:

1. Database hiện tại đã hỗ trợ được phần nào của nghiệp vụ.
2. Phần nào còn thiếu hoặc chưa chặt chẽ.
3. Nên bổ sung schema nào trước để hệ thống bám đúng quy trình.

## 2. Hiện trạng database

Các bảng nghiệp vụ đang có:

- `users`
- `plans`
- `monthly_schedules`
- `schedule_slots`

Ý nghĩa hiện tại:

- `users`: lưu tài khoản, vai trò và trạng thái duyệt tài khoản.
- `plans`: đang được dùng như kế hoạch huấn luyện học kỳ.
- `monthly_schedules`: đang được dùng như lịch huấn luyện theo tháng của lớp.
- `schedule_slots`: lưu các tiết học theo ngày, tiết, môn và nội dung.

Nhận xét nhanh:

- Mô hình hiện tại phù hợp với bài toán "xếp và xem lịch".
- Mô hình hiện tại chưa đủ cho bài toán "điều hành quy trình" vì thiếu dữ liệu về phê duyệt, điều chỉnh, theo dõi, đánh giá, báo cáo và dữ liệu danh mục chuẩn.

## 3. Đối chiếu theo bước nghiệp vụ

| Bước nghiệp vụ | DB hiện tại | Đánh giá | Vì sao chưa đủ |
| --- | --- | --- | --- |
| Lập lịch huấn luyện học kỳ | Có một phần qua `plans` | Đáp ứng một phần | `plans` mới lưu tên, học kỳ, năm, file, mô tả; chưa lưu người tạo, người trình duyệt, trạng thái, lịch sử duyệt |
| Phân công giảng dạy tháng của khoa | Có một phần qua `monthly_schedules` | Đáp ứng một phần | Mới có `plan_id`, `class_name`, `month`, `year`; chưa biết khoa nào lập, ai phụ trách, trạng thái chờ duyệt hay bị trả lại |
| Tổ chức giảng dạy theo ngày/tiết | Có qua `schedule_slots` | Đáp ứng một phần | Chưa gắn giáo viên, phòng học, môn học chuẩn, bài học, trạng thái thực hiện, kết quả thực tế |
| PDT kiểm tra, đối chiếu, xác minh | Chưa có bảng riêng | Chưa đáp ứng | Không có nơi lưu kết quả kiểm tra, nhận xét, người kiểm tra, thời điểm kiểm tra |
| BGH/PDT phê duyệt hoặc từ chối kế hoạch | Chỉ có duyệt tài khoản ở `users.status` | Chưa đáp ứng | Không có trạng thái duyệt cho `plans`, `monthly_schedules`, `reports` hoặc lịch sử hành động duyệt |
| Khoa điều chỉnh kế hoạch | Chưa có | Chưa đáp ứng | Không có bảng phiếu đề nghị thay đổi, nội dung trước/sau khi sửa, lý do, trạng thái xử lý |
| Theo dõi, kiểm tra trong quá trình thực hiện | Chưa có | Chưa đáp ứng | Không có nhật ký huấn luyện, biên bản theo dõi, người theo dõi, kết quả buổi học |
| Đánh giá, nhận xét | Chưa có | Chưa đáp ứng | Không có bảng đánh giá theo tiết, theo ngày hoặc theo tháng |
| Tổng hợp, xây dựng báo cáo | Chưa có | Chưa đáp ứng | Không có bảng báo cáo tháng/kỳ, bản tóm tắt, số liệu kết quả, kiến nghị |
| Quản lý lớp học | Chưa có bảng chuẩn | Chưa đáp ứng | `class_name` đang là chuỗi tự do nên không quản lý được mã lớp, khoa, khóa, trạng thái lớp |
| Quản lý giảng viên | Chưa có bảng chuẩn | Chưa đáp ứng | `users` chỉ có `role`; chưa có mã cán bộ, khoa, thông tin nghiệp vụ giảng dạy |
| Quản lý môn học và lộ trình môn học | Chưa có bảng chuẩn | Chưa đáp ứng | `subject` trong `schedule_slots` chỉ là text, không có cấu trúc môn học, số tiết chuẩn, danh sách bài |
| Quản lý phòng học | Chưa có | Chưa đáp ứng | Không thể kiểm tra xung đột phòng hoặc gắn lịch vào phòng cụ thể |
| Quản lý sinh viên | Chưa có | Chưa đáp ứng | Use case yêu cầu quản lý sinh viên nhưng schema chưa có bảng sinh viên/lớp |

## 4. Những điểm chênh quan trọng trong schema hiện tại

### 4.1. Thiếu dữ liệu workflow

Quy trình trong sơ đồ có nhiều điểm "trình duyệt", "phê duyệt", "chưa đạt yêu cầu", "cập nhật", "chưa phù hợp". Đây là workflow nhiều bước.

Database hiện tại chưa có:

- trạng thái nghiệp vụ cho kế hoạch học kỳ
- trạng thái nghiệp vụ cho lịch tháng
- lịch sử các lần duyệt hoặc trả lại
- người thực hiện hành động duyệt
- lý do từ chối hoặc yêu cầu chỉnh sửa

Nếu không có lớp dữ liệu workflow thì hệ thống chỉ biết "đang có lịch", nhưng không biết lịch đó đang ở bước nào trong quy trình.

### 4.2. Thiếu dữ liệu master chuẩn

Các giá trị như lớp, môn, giảng viên, khoa đang bị lưu rời rạc hoặc chưa tồn tại.

Điều này dẫn đến các vấn đề:

- không ràng buộc được dữ liệu bằng khóa ngoại
- dễ nhập sai tên lớp hoặc tên môn
- khó lọc báo cáo theo khoa, giảng viên, lớp
- không kiểm tra được trùng lịch giáo viên hoặc phòng học

### 4.3. Thiếu dữ liệu thực thi và hậu kiểm

Phần "tổ chức thực hiện", "theo dõi kiểm tra", "đánh giá nhận xét", "tổng kết báo cáo" cần dữ liệu phát sinh sau khi lịch đã được duyệt.

Database hiện tại mới dừng ở lịch dự kiến, chưa có dữ liệu cho:

- buổi học đã diễn ra hay chưa
- dạy đúng nội dung hay không
- có thay đổi thực tế hay không
- ai theo dõi
- nhận xét sau buổi học
- báo cáo tổng hợp cuối kỳ/tháng

## 5. Schema tối thiểu nên bổ sung

Phần này ưu tiên cách làm "đủ dùng cho đúng nghiệp vụ" trước, chưa cố tối ưu quá sớm.

### 5.1. Bảng danh mục nền

#### `departments`

Đề xuất cột chính:

- `id`
- `code`
- `name`
- `description`
- `status`

Vì sao cần:

- Quy trình có actor "Khoa" và nhiều nghiệp vụ theo khoa.
- Cần khóa ngoại để gắn lớp, môn học, giảng viên, lịch tháng với đúng khoa.

#### `classes`

Đề xuất cột chính:

- `id`
- `department_id`
- `code`
- `name`
- `course_year`
- `status`

Vì sao cần:

- Hiện tại `class_name` là text nên không chuẩn hóa.
- Cần quản lý lớp như một thực thể riêng để lọc lịch, báo cáo, sinh viên.

#### `subjects`

Đề xuất cột chính:

- `id`
- `department_id`
- `code`
- `name`
- `total_periods`
- `status`

Vì sao cần:

- `schedule_slots.subject` hiện là chuỗi tự do.
- Cần quản lý môn học như use case đã nêu và phục vụ thống kê khối lượng giảng dạy.

#### `subject_lessons`

Đề xuất cột chính:

- `id`
- `subject_id`
- `lesson_no`
- `title`
- `expected_periods`
- `note`

Vì sao cần:

- Use case yêu cầu quản lý lộ trình môn học.
- Khi gắn tiết học với bài học cụ thể, hệ thống mới kiểm tra được tiến độ thực hiện.

#### `rooms`

Đề xuất cột chính:

- `id`
- `code`
- `name`
- `capacity`
- `room_type`
- `status`

Vì sao cần:

- Quy trình có quản lý phòng học.
- Cần để gắn phòng vào lịch và kiểm tra xung đột sử dụng phòng.

#### `students`

Đề xuất cột chính:

- `id`
- `class_id`
- `student_code`
- `name`
- `date_of_birth`
- `status`

Vì sao cần:

- Use case phòng đào tạo có quản lý sinh viên.
- Đây là dữ liệu đầu vào cho báo cáo lớp và quản lý danh sách học viên.

### 5.2. Mở rộng bảng người dùng

#### Bổ sung vào `users`

Đề xuất thêm:

- `department_id` nullable
- `employee_code` nullable
- `phone` nullable

Vì sao cần:

- Hiện `users.role` chỉ cho biết vai trò, chưa biết người dùng thuộc khoa nào.
- Khi duyệt lịch hoặc lập phân công, cần truy ra đơn vị chịu trách nhiệm.

Lưu ý:

- Sinh viên có thể tách thành bảng `students`.
- Giáo viên, PDT, BGH, Admin vẫn dùng `users`.

### 5.3. Hoàn thiện bảng kế hoạch và lịch

#### Mở rộng `plans`

Đề xuất thêm:

- `created_by`
- `submitted_by`
- `submitted_at`
- `status`
- `current_step`
- `effective_from`
- `effective_to`
- `approved_version`

Ví dụ trạng thái:

- `draft`
- `submitted`
- `training_reviewed`
- `leadership_approved`
- `rejected`

Vì sao cần:

- Kế hoạch học kỳ trong sơ đồ không chỉ được tạo mà còn phải trình duyệt và phê duyệt.
- Cần biết kế hoạch đang ở bước nào và ai chịu trách nhiệm.

#### Mở rộng `monthly_schedules`

Đề xuất thêm:

- `department_id`
- `class_id`
- `created_by`
- `status`
- `submitted_at`
- `approved_at`
- `approved_by`
- `rejection_reason`

Vì sao cần:

- Đây chính là đối tượng "lịch phân công giảng dạy theo tháng".
- Quy trình có kiểm tra, xác minh, trả lại, chỉnh sửa, phê duyệt nên phải có trạng thái nghiệp vụ.

#### Mở rộng `schedule_slots`

Đề xuất thay thế hoặc bổ sung:

- `class_id`
- `teacher_id`
- `subject_id`
- `subject_lesson_id`
- `room_id`
- `slot_status`
- `actual_content`
- `note`

Ràng buộc nên có:

- unique(`monthly_schedule_id`, `date`, `period_number`)

Vì sao cần:

- Một tiết học trong nghiệp vụ không chỉ có ngày và môn.
- Cần biết ai dạy, dạy ở đâu, dạy bài nào, đã hoàn thành hay chưa.

## 6. Các bảng workflow nên thêm mới

### 6.1. `approval_requests`

Đề xuất cột chính:

- `id`
- `entity_type`
- `entity_id`
- `submitted_by`
- `current_step`
- `status`
- `submitted_at`
- `completed_at`

Vì sao cần:

- Cùng một cơ chế duyệt lặp lại ở nhiều đối tượng: kế hoạch học kỳ, lịch tháng, báo cáo.
- Dùng bảng tổng quát sẽ tránh lặp schema duyệt ở nhiều nơi.

### 6.2. `approval_actions`

Đề xuất cột chính:

- `id`
- `approval_request_id`
- `step_code`
- `action`
- `acted_by`
- `acted_at`
- `comment`

Vì sao cần:

- Cần lưu lịch sử từng lần phê duyệt, từ chối, yêu cầu sửa.
- Đây là bằng chứng nghiệp vụ và giúp truy vết khi báo cáo.

### 6.3. `change_requests`

Đề xuất cột chính:

- `id`
- `monthly_schedule_id`
- `schedule_slot_id` nullable
- `requested_by`
- `reason`
- `old_payload`
- `new_payload`
- `status`
- `submitted_at`
- `resolved_at`

Vì sao cần:

- Quy trình và use case đều có "phiếu đề nghị thay đổi kế hoạch giảng dạy".
- Cần lưu nội dung trước và sau khi thay đổi để đối chiếu và duyệt.

## 7. Các bảng theo dõi, đánh giá, báo cáo nên thêm mới

### 7.1. `daily_training_logs`

Đề xuất cột chính:

- `id`
- `schedule_slot_id`
- `teacher_id`
- `actual_date`
- `actual_period_number`
- `result_status`
- `actual_content`
- `issue_note`
- `checked_by`
- `checked_at`

Vì sao cần:

- Dùng cho bước theo dõi, kiểm tra và nhật ký huấn luyện theo ngày.
- Tách riêng khỏi `schedule_slots` để phân biệt dữ liệu kế hoạch và dữ liệu thực tế.

### 7.2. `slot_evaluations`

Đề xuất cột chính:

- `id`
- `schedule_slot_id`
- `evaluator_id`
- `score` nullable
- `comment`
- `created_at`

Vì sao cần:

- Use case giáo viên và PDT đều có phần đánh giá, nhận xét.
- Dữ liệu này phục vụ hậu kiểm và tổng hợp báo cáo.

### 7.3. `monthly_reports`

Đề xuất cột chính:

- `id`
- `monthly_schedule_id`
- `created_by`
- `summary`
- `result_overview`
- `recommendation`
- `status`
- `submitted_at`
- `approved_by`
- `approved_at`

Vì sao cần:

- Quy trình kết thúc bằng tổng kết, xây dựng báo cáo và phê duyệt báo cáo.
- Nếu không có bảng riêng thì không thể quản lý vòng đời báo cáo.

## 8. Thứ tự triển khai migration nên ưu tiên

### Giai đoạn 1: Chuẩn hóa dữ liệu nền

Nên làm trước:

1. `departments`
2. `classes`
3. `subjects`
4. `subject_lessons`
5. `rooms`
6. bổ sung `department_id` vào `users`

Lý do:

- Nếu chưa chuẩn hóa dữ liệu gốc thì các bảng lịch và báo cáo tiếp theo vẫn phải dùng text tự do.
- Làm sau sẽ tốn công migrate lại dữ liệu đã phát sinh.

### Giai đoạn 2: Hoàn thiện bảng lịch hiện có

Nên làm tiếp:

1. mở rộng `plans`
2. mở rộng `monthly_schedules`
3. mở rộng `schedule_slots`
4. thêm unique index và foreign key

Lý do:

- Đây là phần lõi đang được code sử dụng.
- Cần làm chặt ở lớp dữ liệu trước khi thêm workflow nâng cao.

### Giai đoạn 3: Thêm workflow phê duyệt và thay đổi

Nên làm:

1. `approval_requests`
2. `approval_actions`
3. `change_requests`

Lý do:

- Phần này biến hệ thống từ "quản lý lịch" thành "quản lý quy trình".
- Sau khi có workflow, màn hình phê duyệt và trả lại mới làm đúng nghiệp vụ.

### Giai đoạn 4: Thêm dữ liệu thực thi và báo cáo

Nên làm:

1. `daily_training_logs`
2. `slot_evaluations`
3. `monthly_reports`
4. `students`

Lý do:

- Đây là lớp dữ liệu phát sinh sau khi lịch đã đi vào thực hiện.
- Nó phục vụ kiểm tra, đánh giá và tổng kết.

## 9. Ràng buộc dữ liệu nên bổ sung ngay

Ngay cả khi chưa tạo hết bảng mới, nên bổ sung sớm các ràng buộc sau:

- unique(`plans.semester`, `plans.year`) nếu mỗi học kỳ chỉ có một kế hoạch chính
- unique(`monthly_schedules.plan_id`, `monthly_schedules.class_id`, `monthly_schedules.month`, `monthly_schedules.year`)
- unique(`schedule_slots.monthly_schedule_id`, `schedule_slots.date`, `schedule_slots.period_number`)

Vì sao cần:

- Chặn trùng dữ liệu từ lớp database, không phụ thuộc hoàn toàn vào code.
- Giúp dữ liệu nhất quán khi nhiều người thao tác cùng lúc.

## 10. Kết luận

Database hiện tại mới phù hợp cho phiên bản quản lý lịch ở mức cơ bản. Để đáp ứng đúng quy trình điều hành huấn luyện trong sơ đồ, hệ thống cần được mở rộng theo bốn nhóm dữ liệu:

1. Danh mục chuẩn: khoa, lớp, môn, bài học, phòng, sinh viên.
2. Lịch và kế hoạch có workflow: trạng thái, người duyệt, lịch sử duyệt.
3. Điều chỉnh và kiểm tra: phiếu thay đổi, nhật ký, xác minh.
4. Đánh giá và báo cáo: nhận xét theo tiết/buổi và báo cáo tổng hợp.

Nếu cần làm bước tiếp theo, có thể chuyển tài liệu này thành:

- danh sách migration cụ thể cho Laravel
- sơ đồ ERD đề xuất
- hoặc scaffold migration trực tiếp trong project
