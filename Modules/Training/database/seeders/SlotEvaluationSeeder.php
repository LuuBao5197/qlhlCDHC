<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\SlotEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;

class SlotEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $evaluator = User::where('email', 'training@example.com')->first() ?? User::query()->first();

        DailyTrainingLog::query()->take(15)->get()->each(function (DailyTrainingLog $log) use ($evaluator) {
            SlotEvaluation::updateOrCreate(
                ['schedule_slot_id' => $log->schedule_slot_id],
                [
                    'evaluator_id' => $evaluator?->id,
                    'rating_level' => 'kha',
                    'comment' => 'Tiet hoc dat yeu cau, du lieu scaffold de demo danh gia',
                ]
            );
        });
    }
}
