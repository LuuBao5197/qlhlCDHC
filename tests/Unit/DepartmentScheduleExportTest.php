<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleExportPeriod;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleRow;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleRowBuilder;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleXlsxWriter;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Modules\Schedule\Models\ScheduleSlotSubgroup;
use Modules\Training\Models\Room;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;
use Modules\Training\Models\TrainingClass;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DepartmentScheduleExportTest extends TestCase
{
    private int $nextId = 1;

    public function test_consecutive_periods_are_merged_into_one_row(): void
    {
        $slots = collect([6, 7, 8, 9])->map(fn (int $p) => $this->slot(['period_number' => $p]));

        $rows = (new DepartmentScheduleRowBuilder())->build($slots);

        $this->assertCount(1, $rows);
        $this->assertSame(6, $rows[0]->fromPeriod);
        $this->assertSame(9, $rows[0]->toPeriod);
        $this->assertSame('CĐY2B1', $rows[0]->classes);
        $this->assertSame('205/H1', $rows[0]->room);
        $this->assertSame('Bài 3: Nhiệt động hóa học (4)', $rows[0]->lesson);
    }

    public function test_gap_between_periods_creates_separate_rows(): void
    {
        $slots = collect([1, 2, 6, 7])->map(fn (int $p) => $this->slot(['period_number' => $p]));

        $rows = (new DepartmentScheduleRowBuilder())->build($slots);

        $this->assertCount(2, $rows);
        $this->assertSame([1, 2], [$rows[0]->fromPeriod, $rows[0]->toPeriod]);
        $this->assertSame([6, 7], [$rows[1]->fromPeriod, $rows[1]->toPeriod]);
    }

    public function test_different_lessons_in_one_block_are_joined_with_period_counts(): void
    {
        $lessonA = $this->lesson(12, 'Vitamin và chất khoáng');
        $lessonB = $this->lesson(13, 'Thuốc chống sốt rét');
        $slots = collect([
            $this->slot(['period_number' => 1], lesson: $lessonA),
            $this->slot(['period_number' => 2], lesson: $lessonA),
            $this->slot(['period_number' => 3], lesson: $lessonB),
        ]);

        $rows = (new DepartmentScheduleRowBuilder())->build($slots);

        $this->assertCount(1, $rows);
        $this->assertSame('Bài 12: Vitamin và chất khoáng (2). Bài 13: Thuốc chống sốt rét (1)', $rows[0]->lesson);
    }

    public function test_continuation_is_marked_from_prior_usage_and_earlier_periods(): void
    {
        $lesson = $this->lesson(1, 'Cấu tạo nguyên tử');
        $first = $this->slot(['period_number' => 1, 'date' => '2026-09-05'], lesson: $lesson);
        $second = $this->slot(['period_number' => 1, 'date' => '2026-09-07'], lesson: $lesson);

        $rows = (new DepartmentScheduleRowBuilder())->build(collect([$second, $first]));

        $this->assertSame('Bài 1: Cấu tạo nguyên tử (1)', $rows[0]->lesson);
        $this->assertSame('Bài 1: Cấu tạo nguyên tử (tiếp) (1)', $rows[1]->lesson);

        $withPrior = (new DepartmentScheduleRowBuilder())->build(collect([$first]), [1 => [$lesson->id => 3]]);
        $this->assertSame('Bài 1: Cấu tạo nguyên tử (tiếp) (1)', $withPrior[0]->lesson);
    }

    public function test_split_practice_slot_becomes_one_row_per_subgroup(): void
    {
        $teacherA = $this->teacher('Trần Thị Hà');
        $teacherB = $this->teacher('Lê Ngọc Tân');
        $lesson = $this->lesson(8, 'Thực tập kiểm định dược liệu');

        $slots = collect([6, 7, 8, 9])->map(function (int $period) use ($teacherA, $teacherB, $lesson) {
            $slot = $this->slot(['period_number' => $period, 'lesson_type' => 'practice'], lesson: $lesson);
            $slot->setRelation('scheduleSlotSubgroups', collect([
                $this->subgroup('Tổ 1', $teacherA),
                $this->subgroup('Tổ 2', $teacherB),
            ]));

            return $slot;
        });

        $rows = (new DepartmentScheduleRowBuilder())->build($slots);

        $this->assertCount(2, $rows);
        $this->assertSame(['Tổ 1', 'Tổ 2'], [$rows[0]->note, $rows[1]->note]);
        $this->assertSame(['Trần Thị Hà', 'Lê Ngọc Tân'], [$rows[0]->teacher, $rows[1]->teacher]);
        $this->assertSame([6, 9], [$rows[0]->fromPeriod, $rows[0]->toPeriod]);
    }

    public function test_merged_group_lists_all_classes_in_one_row(): void
    {
        $group = new ScheduleSlotGroup();
        $group->forceFill(['id' => 55]);

        $slotA = $this->slot(['schedule_slot_group_id' => 55, 'class_id' => 1], class: $this->trainingClass(1, 'CĐY2B1'));
        $slotB = $this->slot(['schedule_slot_group_id' => 55, 'class_id' => 2], class: $this->trainingClass(2, 'CĐY2B2'));
        $slotA->setRelation('scheduleSlotGroup', $group);
        $slotB->setRelation('scheduleSlotGroup', $group);

        $rows = (new DepartmentScheduleRowBuilder())->build(collect([$slotB, $slotA]));

        $this->assertCount(1, $rows);
        $this->assertSame('CĐY2B1, CĐY2B2', $rows[0]->classes);
    }

    public function test_cancelled_self_study_and_event_slots_are_skipped(): void
    {
        $slots = collect([
            $this->slot(['period_number' => 1, 'slot_status' => 'cancelled']),
            $this->slot(['period_number' => 2, 'assignment_type' => ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY]),
            $this->slot(['period_number' => 3, 'slot_type' => 'event']),
            $this->slot(['period_number' => 4]),
        ]);

        $rows = (new DepartmentScheduleRowBuilder())->build($slots);

        $this->assertCount(1, $rows);
        $this->assertSame(4, $rows[0]->fromPeriod);
    }

    public function test_period_helpers(): void
    {
        $month = DepartmentScheduleExportPeriod::make('month', CarbonImmutable::parse('2026-09-15'));
        $this->assertSame('2026-09-01', $month->start->toDateString());
        $this->assertSame('2026-09-30', $month->end->toDateString());
        $this->assertSame('Phân công giảng dạy tháng 9/2026', $month->title());
        $this->assertSame('(Từ ngày 01/09/2026 đến ngày 30/09/2026)', $month->rangeLabel());
        $this->assertSame('K4_T9', $month->sheetTitle('K4'));

        $week = DepartmentScheduleExportPeriod::make('week', CarbonImmutable::parse('2026-09-30'));
        $this->assertSame('2026-09-28', $week->start->toDateString());
        $this->assertSame('2026-10-04', $week->end->toDateString());

        $day = DepartmentScheduleExportPeriod::make('day', CarbonImmutable::parse('2026-09-28'));
        $this->assertSame('Phân công giảng dạy ngày 28/09/2026', $day->title());

        $this->assertSame(31, mb_strlen($month->sheetTitle(str_repeat('A/B:', 20)), 'UTF-8'));
    }

    public function test_writer_fills_template_with_fewer_rows_than_the_sample(): void
    {
        $sheet = $this->writeAndReload($this->fakeRows(3));

        $this->assertSame('KHOA Y HỌC CƠ SỞ', $sheet->getCell('A2')->getValue());
        $this->assertSame('Số: 05/KH-K4', $sheet->getCell('A3')->getValue());
        $this->assertSame('Thành phố Hồ Chí Minh, ngày 5 tháng 10 năm 2026', $sheet->getCell('H3')->getValue());
        $this->assertSame('Phân công giảng dạy tháng 9/2026', $sheet->getCell('A6')->getValue());
        $this->assertSame('K4_T9', $sheet->getTitle());
        $this->assertNotContains('K8:M8', array_values($sheet->getMergeCells()));
        foreach (['K8', 'K9', 'L9', 'M9', 'K10', 'K13'] as $cell) {
            $this->assertNull($sheet->getCell($cell)->getValue(), 'Cot K:M (so tiet) phai bi loai bo: ' . $cell);
        }

        $this->assertSame('Thứ Hai', $sheet->getCell('B10')->getValue());
        $this->assertSame('Thứ Ba', $sheet->getCell('B11')->getValue());
        $this->assertNull($sheet->getCell('C13')->getValue(), 'Dong mau thua phai bi xoa');
        $this->assertStringContainsString('Ghi chú', (string) $sheet->getCell('A13')->getFormattedValue());

        // Chan trang dich len ngay sau dong du lieu cuoi (dong 12).
        $this->assertSame('CHỦ NHIỆM KHOA', $sheet->getCell('H15')->getValue());
        $this->assertSame('Nguyễn Văn A', $sheet->getCell('H20')->getValue());
        $this->assertContains('H15:J15', array_values($sheet->getMergeCells()));
        $this->assertContains('H20:J20', array_values($sheet->getMergeCells()));
        $this->assertSame('A1:J20', $sheet->getPageSetup()->getPrintArea());
    }

    public function test_writer_fills_template_with_more_rows_than_the_sample(): void
    {
        $sheet = $this->writeAndReload($this->fakeRows(120));

        $lastData = 10 + 120 - 1;
        $this->assertSame('CĐY2B1', $sheet->getCell('C' . $lastData)->getValue());
        $this->assertNull($sheet->getCell('C' . ($lastData + 1))->getValue());
        $this->assertSame('Nguyễn Văn A', $sheet->getCell('H' . ($lastData + 1 + 7))->getValue());
        $this->assertSame('thin', $sheet->getStyle('C' . $lastData)->getBorders()->getLeft()->getBorderStyle());

        // Regression: cot so khong duoc thua huong dinh dang ngay cua cot A.
        foreach (['D' => 1, 'E' => 3] as $column => $expected) {
            $this->assertSame('General', $sheet->getStyle($column . $lastData)->getNumberFormat()->getFormatCode());
            $this->assertSame($expected, (int) $sheet->getCell($column . $lastData)->getValue());
        }
        $this->assertNotSame('General', $sheet->getStyle('A' . $lastData)->getNumberFormat()->getFormatCode());
    }

    public function test_writer_handles_empty_report(): void
    {
        $sheet = $this->writeAndReload([]);

        $this->assertSame('Phân công giảng dạy tháng 9/2026', $sheet->getCell('A6')->getValue());
        $this->assertNull($sheet->getCell('A10')->getValue());
    }

    /**
     * @param array<int, DepartmentScheduleRow> $rows
     */
    private function writeAndReload(array $rows): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $spreadsheet = (new DepartmentScheduleXlsxWriter())->write(
            $rows,
            DepartmentScheduleExportPeriod::make('month', CarbonImmutable::parse('2026-09-01')),
            'K4',
            'Y học cơ sở',
            '05/KH-K4',
            CarbonImmutable::parse('2026-10-05'),
            'Nguyễn Văn A',
        );

        $path = tempnam(sys_get_temp_dir(), 'dse') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $loaded = IOFactory::load($path);
        @unlink($path);

        return $loaded->getSheet(0);
    }

    /**
     * @return array<int, DepartmentScheduleRow>
     */
    private function fakeRows(int $count): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = new DepartmentScheduleRow(
                date: CarbonImmutable::parse('2026-09-07')->addDays($i),
                classes: 'CĐY2B1',
                fromPeriod: 1,
                toPeriod: 3,
                room: '205/H1',
                subject: 'Hóa học',
                lesson: 'Bài 1: Cấu tạo nguyên tử (3)',
                teacher: 'Phạm Đoàn Anh Ninh',
                note: '',
            );
        }

        return $rows;
    }

    private function slot(array $attributes = [], ?SubjectLesson $lesson = null, ?TrainingClass $class = null): ScheduleSlot
    {
        $class ??= $this->trainingClass(1, 'CĐY2B1');
        $slot = new ScheduleSlot();
        $slot->forceFill(array_merge([
            'id' => $this->nextId++,
            'class_id' => $class->id,
            'subject_id' => 10,
            'subject_lesson_id' => $lesson?->id ?? 100,
            'slot_type' => 'subject',
            'slot_status' => 'planned',
            'date' => '2026-09-07',
            'period_number' => 1,
            'lesson_type' => 'theory',
        ], $attributes));

        $subject = new Subject();
        $subject->forceFill(['id' => 10, 'name' => 'Hóa học']);
        $room = new Room();
        $room->forceFill(['id' => 1, 'code' => '205/H1']);

        $slot->setRelation('trainingClass', $class);
        $slot->setRelation('teacher', $this->teacher('Phạm Đoàn Anh Ninh'));
        $slot->setRelation('subjectModel', $subject);
        $slot->setRelation('subjectLesson', $lesson ?? $this->lesson(3, 'Nhiệt động hóa học', id: 100));
        $slot->setRelation('room', $room);
        $slot->setRelation('scheduleSlotSubgroups', new Collection());

        return $slot;
    }

    private function lesson(int $no, string $title, bool $isTest = false, ?int $id = null): SubjectLesson
    {
        $lesson = new SubjectLesson();
        $lesson->forceFill(['id' => $id ?? 1000 + $no, 'lesson_no' => $no, 'title' => $title, 'is_regular_test' => $isTest]);

        return $lesson;
    }

    private function teacher(string $name): Teacher
    {
        $teacher = new Teacher();
        $teacher->forceFill(['id' => crc32($name) % 100000, 'name' => $name]);

        return $teacher;
    }

    private function trainingClass(int $id, string $code): TrainingClass
    {
        $class = new TrainingClass();
        $class->forceFill(['id' => $id, 'code' => $code, 'name' => $code]);

        return $class;
    }

    private function subgroup(string $label, Teacher $teacher): ScheduleSlotSubgroup
    {
        $subgroup = new ScheduleSlotSubgroup();
        $subgroup->forceFill(['id' => $this->nextId++, 'group_label' => $label]);
        $subgroup->setRelation('teacher', $teacher);
        $subgroup->setRelation('room', null);

        return $subgroup;
    }
}
