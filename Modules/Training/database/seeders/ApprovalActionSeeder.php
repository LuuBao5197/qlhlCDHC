<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalActionSeeder extends Seeder
{
    public function run(): void
    {
        $trainingUser = User::where('email', 'training@example.com')->first() ?? User::query()->first();
        $leaderUser = User::where('email', 'leader@example.com')->first() ?? User::query()->first();

        ApprovalRequest::query()->get()->each(function (ApprovalRequest $approvalRequest) use ($trainingUser, $leaderUser) {
            ApprovalAction::firstOrCreate(
                [
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'submitted',
                    'action' => 'submit',
                ],
                [
                    'acted_by' => $trainingUser?->id,
                    'acted_at' => $approvalRequest->submitted_at ?? now(),
                    'comment' => 'Ho so da duoc trinh duyet',
                ]
            );

            ApprovalAction::firstOrCreate(
                [
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => $approvalRequest->current_step,
                    'action' => 'comment',
                ],
                [
                    'acted_by' => $leaderUser?->id,
                    'acted_at' => now(),
                    'comment' => 'Scaffold du lieu phe duyet de demo quy trinh',
                ]
            );
        });
    }
}
