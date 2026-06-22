<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Models\Student;
use Modules\Training\Models\TrainingClass;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $classes = TrainingClass::query()
            ->orderBy('code')
            ->get()
            ->values();

        $classes->each(function (TrainingClass $trainingClass, int $classIndex): void {
            for ($number = 1; $number <= 50; $number++) {
                Student::updateOrCreate(
                    ['student_code' => $trainingClass->code . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT)],
                    [
                        'class_id' => $trainingClass->id,
                        'name' => $this->generateVietnameseName($classIndex, $number),
                        'date_of_birth' => now()->subYears(20)->subDays(($classIndex * 3) + $number)->toDateString(),
                        'status' => 'active',
                    ]
                );
            }
        });
    }

    private function generateVietnameseName(int $classIndex, int $studentNumber): string
    {
        $surnames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Ngô'];
        $middles = ['Văn', 'Thị', 'Minh', 'Thùy', 'Đức', 'Ngọc', 'Thanh', 'Hoài', 'Gia', 'Khánh'];
        $givens = ['Anh', 'Bình', 'Châu', 'Dũng', 'Hà', 'Hạnh', 'Hải', 'Hoa', 'Huy', 'Khôi', 'Lan', 'Linh', 'Mai', 'Nam', 'Nga', 'Nhung', 'Phúc', 'Quân', 'Trang', 'Tú', 'Vy', 'Yến'];

        $surname = $surnames[($classIndex + $studentNumber) % count($surnames)];
        $middle = $middles[($classIndex * 2 + $studentNumber) % count($middles)];
        $given = $givens[($classIndex * 3 + $studentNumber) % count($givens)];

        return trim($surname . ' ' . $middle . ' ' . $given);
    }
}
