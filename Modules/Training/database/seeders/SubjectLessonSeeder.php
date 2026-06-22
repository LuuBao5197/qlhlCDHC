<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;

class SubjectLessonSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        Subject::query()
            ->orderBy('code')
            ->get()
            ->each(function (Subject $subject): void {
                $lessonTopics = $this->lessonTopicsFor($subject->code, $subject->name);

                foreach ($lessonTopics as $lessonNo => $topic) {
                    SubjectLesson::firstOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'lesson_no' => $lessonNo,
                        ],
                        [
                            'title' => $topic['title'],
                            'expected_periods' => $topic['expected_periods'],
                            'note' => $topic['note'],
                        ]
                    );
                }
            });
    }

    /**
     * @return array<int, array{title: string, expected_periods: int, note: string}>
     */
    private function lessonTopicsFor(string $subjectCode, string $subjectName): array
    {
        return match ($subjectCode) {
            'DUOC-001' => [
                ['title' => 'Đại cương về dược và vai trò của dược sĩ', 'expected_periods' => 2, 'note' => 'Nắm vai trò của ngành Dược trong hệ thống y tế quân y.'],
                ['title' => 'Phân loại thuốc và các dạng bào chế', 'expected_periods' => 2, 'note' => 'Làm quen với các nhóm thuốc và dạng bào chế thông dụng.'],
                ['title' => 'Dược động học và dược lực học cơ bản', 'expected_periods' => 2, 'note' => 'Hiểu cách thuốc đi vào, tác động và thải trừ khỏi cơ thể.'],
                ['title' => 'Tương tác thuốc và an toàn sử dụng thuốc', 'expected_periods' => 2, 'note' => 'Nhận diện nguy cơ dùng thuốc không hợp lý.'],
                ['title' => 'Quy trình cấp phát và bảo quản thuốc', 'expected_periods' => 2, 'note' => 'Áp dụng trong kho thuốc và nhà thuốc bệnh viện.'],
                ['title' => 'Thực hành tư vấn sử dụng thuốc cho người bệnh', 'expected_periods' => 2, 'note' => 'Rèn kỹ năng giao tiếp và tư vấn.'],
            ],
            'DUOC-002' => [
                ['title' => 'Nguyên liệu dược và tá dược thường dùng', 'expected_periods' => 2, 'note' => 'Giới thiệu các thành phần cơ bản trong công thức thuốc.'],
                ['title' => 'Kỹ thuật pha chế và cân đong', 'expected_periods' => 2, 'note' => 'Thực hành chuẩn bị nguyên liệu chính xác.'],
                ['title' => 'Bào chế thuốc viên và thuốc bột', 'expected_periods' => 3, 'note' => 'Tập trung vào quy trình tạo dạng thuốc rắn.'],
                ['title' => 'Bào chế thuốc tiêm và dung dịch vô khuẩn', 'expected_periods' => 3, 'note' => 'Lưu ý điều kiện sạch và vô khuẩn trong sản xuất.'],
                ['title' => 'Kiểm nghiệm chất lượng trong bào chế', 'expected_periods' => 2, 'note' => 'Đánh giá chất lượng thành phẩm trước khi phát hành.'],
                ['title' => 'Thực hành mô phỏng quy trình bào chế', 'expected_periods' => 3, 'note' => 'Tổng hợp kỹ năng qua tình huống thực tế.'],
            ],
            'DUOC-003' => [
                ['title' => 'Giới thiệu dược lâm sàng tại bệnh viện', 'expected_periods' => 2, 'note' => 'Hiểu vị trí của dược sĩ lâm sàng trong điều trị.'],
                ['title' => 'Khai thác bệnh sử và danh mục thuốc đang dùng', 'expected_periods' => 2, 'note' => 'Phục vụ đánh giá điều trị an toàn.'],
                ['title' => 'Theo dõi hiệu quả và phản ứng có hại của thuốc', 'expected_periods' => 2, 'note' => 'Nhận biết dấu hiệu bất thường khi dùng thuốc.'],
                ['title' => 'Hiệu chỉnh liều và hỗ trợ lựa chọn thuốc', 'expected_periods' => 2, 'note' => 'Liên hệ lâm sàng, xét nghiệm và chức năng cơ quan.'],
                ['title' => 'Tư vấn người bệnh tại buồng bệnh', 'expected_periods' => 2, 'note' => 'Giao tiếp ngắn gọn, rõ ràng với người bệnh và thân nhân.'],
                ['title' => 'Báo cáo và ghi nhận can thiệp dược', 'expected_periods' => 2, 'note' => 'Chuẩn hóa hồ sơ dược lâm sàng.'],
            ],
            'DD-001' => [
                ['title' => 'Vai trò người điều dưỡng và đạo đức nghề nghiệp', 'expected_periods' => 2, 'note' => 'Xây nền tảng cho sinh viên điều dưỡng.'],
                ['title' => 'Quy trình chăm sóc người bệnh cơ bản', 'expected_periods' => 3, 'note' => 'Trình tự chăm sóc từ nhận định đến lượng giá.'],
                ['title' => 'Đo dấu hiệu sinh tồn và ghi chép hồ sơ', 'expected_periods' => 2, 'note' => 'Rèn thao tác đo và ghi nhận đúng quy định.'],
                ['title' => 'Kỹ thuật tiêm, truyền và an toàn người bệnh', 'expected_periods' => 3, 'note' => 'Chú trọng thực hành an toàn, đúng quy trình.'],
                ['title' => 'Chăm sóc bệnh nhân nằm viện dài ngày', 'expected_periods' => 2, 'note' => 'Phòng biến chứng và hỗ trợ phục hồi.'],
                ['title' => 'Thực hành giao tiếp và giáo dục sức khỏe', 'expected_periods' => 2, 'note' => 'Kỹ năng mềm bắt buộc của điều dưỡng.'],
            ],
            'DD-002' => [
                ['title' => 'Đại cương chăm sóc người bệnh nội khoa', 'expected_periods' => 2, 'note' => 'Nắm mô hình chăm sóc trong các khoa nội.'],
                ['title' => 'Chăm sóc người bệnh tim mạch', 'expected_periods' => 2, 'note' => 'Theo dõi sát dấu hiệu và dùng thuốc theo y lệnh.'],
                ['title' => 'Chăm sóc người bệnh hô hấp', 'expected_periods' => 2, 'note' => 'Hỗ trợ thở, hút đờm và theo dõi oxy máu.'],
                ['title' => 'Chăm sóc người bệnh tiêu hóa và gan mật', 'expected_periods' => 2, 'note' => 'Theo dõi dinh dưỡng và dịch truyền.'],
                ['title' => 'Phòng ngừa tai biến thường gặp trong nội khoa', 'expected_periods' => 2, 'note' => 'Nhận diện dấu hiệu cảnh báo sớm.'],
                ['title' => 'Lâm sàng mô phỏng tình huống nội khoa', 'expected_periods' => 2, 'note' => 'Luyện phản xạ xử trí tình huống.'],
            ],
            'DD-003' => [
                ['title' => 'Nguyên tắc kiểm soát nhiễm khuẩn', 'expected_periods' => 2, 'note' => 'Khái quát các biện pháp phòng ngừa lây nhiễm.'],
                ['title' => 'Rửa tay thường quy và khử khuẩn bề mặt', 'expected_periods' => 2, 'note' => 'Thực hành bước cơ bản nhưng rất quan trọng.'],
                ['title' => 'Phân loại chất thải y tế', 'expected_periods' => 2, 'note' => 'Nhận biết và xử lý chất thải theo nhóm.'],
                ['title' => 'Vệ sinh dụng cụ và tiệt khuẩn', 'expected_periods' => 3, 'note' => 'Quy trình xử lý vật tư sau sử dụng.'],
                ['title' => 'Phòng ngừa nhiễm khuẩn hô hấp và tiêu hóa', 'expected_periods' => 2, 'note' => 'Áp dụng trong khoa lâm sàng và khu cách ly.'],
                ['title' => 'Kiểm tra tuân thủ và báo cáo sự cố', 'expected_periods' => 2, 'note' => 'Củng cố thói quen thực hành an toàn.'],
            ],
            'YSDK-001' => [
                ['title' => 'Tổng quan giải phẫu người và thuật ngữ cơ bản', 'expected_periods' => 2, 'note' => 'Đặt nền tảng cho các học phần lâm sàng.'],
                ['title' => 'Hệ xương, khớp và cơ', 'expected_periods' => 2, 'note' => 'Nắm cấu trúc vận động cơ bản của cơ thể.'],
                ['title' => 'Hệ tuần hoàn và hô hấp', 'expected_periods' => 2, 'note' => 'Liên hệ với biểu hiện lâm sàng thường gặp.'],
                ['title' => 'Hệ tiêu hóa và tiết niệu', 'expected_periods' => 2, 'note' => 'Hiểu chức năng từng cơ quan trong cơ thể.'],
                ['title' => 'Sinh lý các cơ quan quan trọng', 'expected_periods' => 2, 'note' => 'Làm rõ hoạt động sinh lý bình thường.'],
                ['title' => 'Thực hành nhận diện mô hình giải phẫu', 'expected_periods' => 2, 'note' => 'Ôn tập trên tranh, mô hình và hình ảnh.'],
            ],
            'YSDK-002' => [
                ['title' => 'Tiếp cận người bệnh nội khoa tại tuyến cơ sở', 'expected_periods' => 2, 'note' => 'Nâng cao khả năng khám và xử trí ban đầu.'],
                ['title' => 'Các hội chứng nội khoa thường gặp', 'expected_periods' => 2, 'note' => 'Xử lý sốt, đau, khó thở và rối loạn tiêu hóa.'],
                ['title' => 'Đọc kết quả xét nghiệm cơ bản', 'expected_periods' => 2, 'note' => 'Biết liên hệ xét nghiệm với tình trạng người bệnh.'],
                ['title' => 'Chẩn đoán sơ bộ và chỉ định chuyển tuyến', 'expected_periods' => 2, 'note' => 'Rèn tư duy lâm sàng an toàn.'],
                ['title' => 'Kê đơn thuốc thông dụng theo hướng dẫn', 'expected_periods' => 2, 'note' => 'Phù hợp phạm vi hành nghề của y sĩ.'],
                ['title' => 'Thực hành tình huống nội khoa', 'expected_periods' => 2, 'note' => 'Luyện phản xạ trong ca bệnh mô phỏng.'],
            ],
            'YSDK-003' => [
                ['title' => 'Khám ngoại khoa ban đầu', 'expected_periods' => 2, 'note' => 'Nhận diện tổn thương và chỉ định xử trí ban đầu.'],
                ['title' => 'Vết thương, chấn thương và sơ cứu', 'expected_periods' => 2, 'note' => 'Thực hành xử trí vết thương cơ bản.'],
                ['title' => 'Băng bó, cố định và chăm sóc sau thủ thuật', 'expected_periods' => 2, 'note' => 'Rèn kỹ năng thủ thuật thường gặp.'],
                ['title' => 'Theo dõi hậu phẫu và dấu hiệu nguy hiểm', 'expected_periods' => 2, 'note' => 'Phát hiện sớm biến chứng sau mổ.'],
                ['title' => 'Chuẩn bị người bệnh trước phẫu thuật', 'expected_periods' => 2, 'note' => 'Đảm bảo an toàn trước can thiệp ngoại khoa.'],
                ['title' => 'Thực hành mô phỏng tại phòng kỹ năng', 'expected_periods' => 2, 'note' => 'Tổng hợp quy trình xử trí ngoại khoa.'],
            ],
            'YHCS-001' => [
                ['title' => 'Đại cương vi sinh học y học', 'expected_periods' => 2, 'note' => 'Hiểu vai trò vi sinh trong bệnh học.'],
                ['title' => 'Vi khuẩn thường gặp trong lâm sàng', 'expected_periods' => 2, 'note' => 'Nhận diện tác nhân gây bệnh phổ biến.'],
                ['title' => 'Virus và cơ chế gây bệnh', 'expected_periods' => 2, 'note' => 'Liên hệ giữa virus và bệnh truyền nhiễm.'],
                ['title' => 'Kỹ thuật nuôi cấy và nhuộm vi sinh', 'expected_periods' => 3, 'note' => 'Thực hành cơ bản trong phòng xét nghiệm.'],
                ['title' => 'Kiểm soát nhiễm khuẩn trong xét nghiệm', 'expected_periods' => 2, 'note' => 'Đảm bảo an toàn sinh học.'],
                ['title' => 'Ôn tập tình huống bệnh truyền nhiễm', 'expected_periods' => 2, 'note' => 'Củng cố kiến thức qua tình huống thực tế.'],
            ],
            'YHCS-002' => [
                ['title' => 'Cơ chế bệnh sinh và đáp ứng của cơ thể', 'expected_periods' => 2, 'note' => 'Nắm nền tảng sinh lý bệnh.'],
                ['title' => 'Rối loạn tuần hoàn và hô hấp', 'expected_periods' => 2, 'note' => 'Liên hệ với biểu hiện thường gặp trên lâm sàng.'],
                ['title' => 'Rối loạn chuyển hóa và nội tiết', 'expected_periods' => 2, 'note' => 'Hiểu ảnh hưởng đến tình trạng người bệnh.'],
                ['title' => 'Miễn dịch và phản ứng viêm', 'expected_periods' => 2, 'note' => 'Làm rõ cơ chế bảo vệ của cơ thể.'],
                ['title' => 'Bệnh lý mạn tính thường gặp', 'expected_periods' => 2, 'note' => 'Gắn với chăm sóc và theo dõi lâu dài.'],
                ['title' => 'Thực hành phân tích ca bệnh', 'expected_periods' => 2, 'note' => 'Ứng dụng kiến thức vào tình huống lâm sàng.'],
            ],
            'YHCS-003' => [
                ['title' => 'Cấu trúc và chức năng hóa sinh của cơ thể', 'expected_periods' => 2, 'note' => 'Giới thiệu các phản ứng nền tảng.'],
                ['title' => 'Protein, lipid và glucid', 'expected_periods' => 2, 'note' => 'Tìm hiểu các nhóm chất chính trong cơ thể.'],
                ['title' => 'Men, vitamin và khoáng chất', 'expected_periods' => 2, 'note' => 'Liên hệ rối loạn thiếu hụt dưỡng chất.'],
                ['title' => 'Xét nghiệm hóa sinh thường dùng', 'expected_periods' => 2, 'note' => 'Đọc các chỉ số xét nghiệm cơ bản.'],
                ['title' => 'Rối loạn chuyển hóa gặp trong thực hành', 'expected_periods' => 2, 'note' => 'Phân tích các ca bệnh điển hình.'],
                ['title' => 'Ôn tập và ứng dụng hóa sinh y học', 'expected_periods' => 2, 'note' => 'Tổng hợp kiến thức theo tình huống.'],
            ],
            'KHCB-001' => [
                ['title' => 'Quy tắc đạo đức nghề y', 'expected_periods' => 2, 'note' => 'Nhấn mạnh trách nhiệm nghề nghiệp.'],
                ['title' => 'Pháp luật cơ bản liên quan đến y tế', 'expected_periods' => 2, 'note' => 'Giúp người học hiểu khung pháp lý cần tuân thủ.'],
                ['title' => 'Quyền và nghĩa vụ của người bệnh', 'expected_periods' => 2, 'note' => 'Phục vụ giao tiếp và xử lý tình huống thực tế.'],
                ['title' => 'Bảo mật thông tin và hồ sơ y tế', 'expected_periods' => 2, 'note' => 'Áp dụng trong môi trường bệnh viện.'],
                ['title' => 'Xử lý tình huống vi phạm đạo đức', 'expected_periods' => 2, 'note' => 'Học qua các ca điển hình.'],
                ['title' => 'Ôn tập và thảo luận tình huống', 'expected_periods' => 2, 'note' => 'Củng cố nhận thức nghề nghiệp.'],
            ],
            'KHCB-002' => [
                ['title' => 'Tâm lý người bệnh trong môi trường điều trị', 'expected_periods' => 2, 'note' => 'Hiểu đặc điểm tâm lý khi nằm viện.'],
                ['title' => 'Kỹ năng lắng nghe và giao tiếp hiệu quả', 'expected_periods' => 2, 'note' => 'Phục vụ chăm sóc và tư vấn người bệnh.'],
                ['title' => 'Giao tiếp với đồng nghiệp và cấp trên', 'expected_periods' => 2, 'note' => 'Xây dựng văn hóa phối hợp chuyên nghiệp.'],
                ['title' => 'Ứng xử với người bệnh và thân nhân', 'expected_periods' => 2, 'note' => 'Giữ thái độ chuẩn mực, tôn trọng.'],
                ['title' => 'Xử lý xung đột trong môi trường bệnh viện', 'expected_periods' => 2, 'note' => 'Thực hành kỹ năng mềm cần thiết.'],
                ['title' => 'Thực hành giao tiếp tình huống mô phỏng', 'expected_periods' => 2, 'note' => 'Áp dụng trên kịch bản thực tế.'],
            ],
            'KHCB-003' => [
                ['title' => 'Tổng quan hệ thống thông tin y tế', 'expected_periods' => 2, 'note' => 'Giới thiệu ứng dụng số trong bệnh viện.'],
                ['title' => 'Soạn thảo văn bản và bảng biểu cơ bản', 'expected_periods' => 2, 'note' => 'Rèn kỹ năng văn phòng trong công việc.'],
                ['title' => 'Làm việc với bảng tính và dữ liệu bệnh án', 'expected_periods' => 2, 'note' => 'Ứng dụng trong báo cáo và thống kê.'],
                ['title' => 'An toàn dữ liệu và sao lưu thông tin', 'expected_periods' => 2, 'note' => 'Bảo vệ hồ sơ điện tử và quyền riêng tư.'],
                ['title' => 'Trình bày báo cáo học tập', 'expected_periods' => 2, 'note' => 'Học cách tổng hợp và thuyết trình.'],
                ['title' => 'Thực hành nhập liệu và xử lý tình huống', 'expected_periods' => 2, 'note' => 'Củng cố kỹ năng tin học ứng dụng.'],
            ],
            default => [
                ['title' => $subjectName . ' - Đại cương', 'expected_periods' => 2, 'note' => 'Bài mở đầu của học phần.'],
                ['title' => $subjectName . ' - Nội dung 1', 'expected_periods' => 2, 'note' => 'Triển khai kiến thức trọng tâm.'],
                ['title' => $subjectName . ' - Nội dung 2', 'expected_periods' => 2, 'note' => 'Củng cố bằng ví dụ và thực hành.'],
                ['title' => $subjectName . ' - Nội dung 3', 'expected_periods' => 2, 'note' => 'Liên hệ thực tế chuyên môn.'],
                ['title' => $subjectName . ' - Nội dung 4', 'expected_periods' => 2, 'note' => 'Luyện tập và trao đổi tình huống.'],
                ['title' => $subjectName . ' - Ôn tập', 'expected_periods' => 2, 'note' => 'Tổng kết nội dung học phần.'],
            ],
        };
    }
}
