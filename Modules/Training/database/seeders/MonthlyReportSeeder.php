<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\MonthlySchedule;

class MonthlyReportSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('email', 'training@example.com')->first() ?? User::query()->first();
        $approver = User::where('email', 'leader@example.com')->first() ?? User::query()->first();

        MonthlySchedule::query()->get()->each(function (MonthlySchedule $monthlySchedule) use ($creator, $approver) {
            MonthlyReport::updateOrCreate(
                ['monthly_schedule_id' => $monthlySchedule->id],
                [
                    'created_by' => $creator?->id,
                    'summary' => 'Bao cao tong hop thang ' . $monthlySchedule->month . '/' . $monthlySchedule->year,
                    'result_overview' => 'Tien do thuc hien co ban dap ung ke hoach de ra.',
                    'recommendation' => 'Tiep tuc theo doi sat cac de nghi dieu chinh neu co.',
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'approved_by' => $approver?->id,
                    'approved_at' => now(),
                ]
            );
        });
    }
}
