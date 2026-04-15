<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;

class ApprovalRequestSeeder extends Seeder
{
    public function run(): void
    {
        $submittedBy = User::where('email', 'training@example.com')->first() ?? User::query()->first();

        Plans::query()->get()->each(function (Plans $plan) use ($submittedBy) {
            ApprovalRequest::updateOrCreate(
                [
                    'entity_type' => Plans::class,
                    'entity_id' => $plan->id,
                ],
                [
                    'submitted_by' => $submittedBy?->id,
                    'current_step' => 'leadership_review',
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'completed_at' => null,
                ]
            );
        });

        MonthlySchedule::query()->limit(3)->get()->each(function (MonthlySchedule $monthlySchedule) use ($submittedBy) {
            ApprovalRequest::updateOrCreate(
                [
                    'entity_type' => MonthlySchedule::class,
                    'entity_id' => $monthlySchedule->id,
                ],
                [
                    'submitted_by' => $submittedBy?->id,
                    'current_step' => 'training_review',
                    'status' => 'processing',
                    'submitted_at' => now(),
                    'completed_at' => null,
                ]
            );
        });
    }
}
