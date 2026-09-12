<?php

namespace App\Services;

use App\Enums\InternalNotificationType;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InternalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ChangeRequest;

class InternalNotificationService
{
    /**
     * @param iterable<User> $users
     */
    public function notifyUsers(iterable $users, InternalNotification $notification): void
    {
        $recipients = collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique(fn (User $user) => (string) $user->id)
            ->values();

        foreach ($recipients as $recipient) {
            if ($this->hasDuplicate($recipient, $notification->dedupeKey)) {
                continue;
            }

            $recipient->notify($notification);
        }
    }

    public function notifyAccountCreated(User $user, User $actor): void
    {
        $this->notifyUsers([$user], $this->makeNotification(
            InternalNotificationType::USER_REGISTRATION_APPROVED,
            'Tai khoan da duoc tao',
            'Tai khoan ' . $user->name . ' da duoc Admin tao va cap mat khau mac dinh. Vui long doi mat khau ngay lan dang nhap dau tien.',
            route('settings'),
            'account-created:' . $user->id,
            [
                'user_id' => $user->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * Bao cho toan bo Admin khi co yeu cau quen mat khau moi can duyet — thay cho
     * viec gui email vi he thong chay noi bo.
     */
    public function notifyPasswordResetRequested(PasswordResetRequest $resetRequest): void
    {
        $requester = $resetRequest->user;

        $this->notifyUsers($this->roleRecipients([User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::PASSWORD_RESET_REQUESTED,
            'Co yeu cau dat lai mat khau',
            ($requester?->name ?? 'Mot nguoi dung') . ' (' . ($requester?->email ?? '') . ') vua gui yeu cau dat lai mat khau.',
            route('admin.index'),
            'password-reset-requested:' . $resetRequest->id,
            [
                'password_reset_request_id' => $resetRequest->id,
                'user_id' => $resetRequest->user_id,
            ]
        ));
    }

    public function notifySemesterPlanSubmitted(Plans $plan, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::SEMESTER_PLAN_SUBMITTED,
            'Ke hoach hoc ky da duoc gui',
            $plan->name . ' vua duoc gui len Phong Dao tao va dang cho duyet.',
            route('schedule.show', $plan->id),
            'semester-plan-submitted:' . $plan->id . ':' . optional($plan->submitted_at)->timestamp,
            [
                'plan_id' => $plan->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifySemesterPlanTrainingOfficeReviewed(Plans $plan, User $actor, bool $approved): void
    {
        if ($approved) {
            $this->notifyUsers($this->roleRecipients([User::ROLE_LEADERSHIP, User::ROLE_ADMIN]), $this->makeNotification(
                InternalNotificationType::SEMESTER_PLAN_SUBMITTED,
                'Ke hoach hoc ky da duoc trinh len Ban Giam hieu',
                $plan->name . ' da duoc Phong Dao tao duyet va trinh len Ban Giam hieu.',
                route('schedule.show', $plan->id),
                'semester-plan-forwarded-to-leadership:' . $plan->id . ':' . now()->timestamp,
                [
                    'plan_id' => $plan->id,
                    'actor_id' => $actor->id,
                ]
            ));

            return;
        }

        $recipients = $this->recipientUsers($plan->submittedBy)
            ->merge($this->recipientUsers($plan->createdBy));

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::SEMESTER_PLAN_REJECTED,
            'Ke hoach hoc ky bi tu choi',
            $plan->name . ' da bi Phong Dao tao tu choi.',
            route('schedule.show', $plan->id),
            'semester-plan-training-office-reviewed:' . $plan->id . ':' . now()->timestamp,
            [
                'plan_id' => $plan->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifySemesterPlanReviewed(Plans $plan, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::SEMESTER_PLAN_APPROVED
            : InternalNotificationType::SEMESTER_PLAN_REJECTED;

        $recipients = $this->recipientUsers($plan->submittedBy)
            ->merge($this->recipientUsers($plan->createdBy));

        if ($approved) {
            $recipients = $recipients->merge($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]));
        }

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'Ke hoach hoc ky da duoc phe duyet' : 'Ke hoach hoc ky bi tu choi',
            $approved
                ? $plan->name . ' da duoc Ban Giam hieu phe duyet.'
                : $plan->name . ' da bi Ban Giam hieu tu choi.',
            route('schedule.show', $plan->id),
            'semester-plan-reviewed:' . $plan->id . ':' . now()->timestamp,
            [
                'plan_id' => $plan->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyScheduleChangeRequestSubmitted(ChangeRequest $changeRequest, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::SCHEDULE_CHANGE_REQUEST_SUBMITTED,
            'Co phieu thay doi lich moi',
            'Phieu thay doi lich ' . $changeRequest->id . ' vua duoc tao va dang cho xu ly.',
            route('schedule.index'),
            'schedule-change-request-submitted:' . $changeRequest->id,
            [
                'change_request_id' => $changeRequest->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyScheduleChangeRequestReviewed(ChangeRequest $changeRequest, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::SCHEDULE_CHANGE_REQUEST_APPROVED
            : InternalNotificationType::SCHEDULE_CHANGE_REQUEST_REJECTED;

        $this->notifyUsers($this->recipientUsers($changeRequest->requestedBy), $this->makeNotification(
            $type,
            $approved ? 'Phieu thay doi lich da duoc duyet' : 'Phieu thay doi lich bi tu choi',
            'Phieu thay doi lich ' . $changeRequest->id . ($approved ? ' da duoc phe duyet.' : ' da bi tu choi.'),
            route('schedule.index'),
            'schedule-change-request-reviewed:' . $changeRequest->id . ':' . ($approved ? 'approved' : 'rejected'),
            [
                'change_request_id' => $changeRequest->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyDepartmentMonthlyAssignmentBatchSubmitted(DepartmentMonthlyAssignmentBatch $batch, User $actor): void
    {
        $this->notifyUsers($this->departmentLeadershipRecipients((int) $batch->department_id), $this->makeNotification(
            InternalNotificationType::ASSIGNMENT_BATCH_SUBMITTED,
            'Batch phan cong da duoc gui',
            'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' dang cho Lanh dao Khoa duyet.',
            route('department-monthly-assignment-batches.show', $batch->id),
            'assignment-batch-submitted:' . $batch->id,
            [
                'batch_id' => $batch->id,
                'department_id' => $batch->department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * Lanh dao Khoa duyet/tu choi batch cua chinh Khoa minh.
     * Duyet: bao PDT (batch da san sang cho PDT duyet). Tu choi: bao lai Khoa.
     */
    public function notifyDepartmentMonthlyAssignmentBatchReviewed(DepartmentMonthlyAssignmentBatch $batch, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::ASSIGNMENT_BATCH_APPROVED
            : InternalNotificationType::ASSIGNMENT_BATCH_RETURNED;

        $message = $approved
            ? 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da duoc Lanh dao Khoa duyet, dang cho PDT duyet.'
            : 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da bi Lanh dao Khoa tra ve.';

        $recipients = $approved
            ? $this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN])
            : $this->departmentStaffRecipients((int) $batch->department_id)->merge($this->recipientUsers($batch->submittedBy));

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'Batch phan cong da duoc Lanh dao Khoa duyet' : 'Batch phan cong bi Lanh dao Khoa tra ve',
            $message,
            route('department-monthly-assignment-batches.show', $batch->id),
            'assignment-batch-reviewed:' . $batch->id . ':' . ($approved ? 'approved' : 'returned'),
            [
                'batch_id' => $batch->id,
                'department_id' => $batch->department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * PDT duyet/tra ve batch da duoc Lanh dao Khoa duyet. Ca 2 truong hop deu bao lai Khoa.
     */
    public function notifyDepartmentMonthlyAssignmentBatchTrainingOfficeReviewed(DepartmentMonthlyAssignmentBatch $batch, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED
            : InternalNotificationType::ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED;

        $message = $approved
            ? 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da duoc PDT duyet, san sang de tong hop.'
            : 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da bi PDT tra ve.';

        $recipients = $this->departmentStaffRecipients((int) $batch->department_id)->merge($this->recipientUsers($batch->submittedBy));

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'Batch phan cong da duoc PDT duyet' : 'Batch phan cong bi PDT tra ve',
            $message,
            route('department-monthly-assignment-batches.show', $batch->id),
            'assignment-batch-training-office-reviewed:' . $batch->id . ':' . ($approved ? 'approved' : 'returned'),
            [
                'batch_id' => $batch->id,
                'department_id' => $batch->department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * Batch phan cong bi he thong tu dong mo lai ve draft do phat sinh ngay nghi le/tet dot xuat.
     */
    public function notifyDepartmentMonthlyAssignmentBatchReopenedForHoliday(DepartmentMonthlyAssignmentBatch $batch, User $actor, string $holidayName): void
    {
        $recipients = $this->departmentStaffRecipients((int) $batch->department_id)
            ->merge($this->recipientUsers($batch->submittedBy));

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::ASSIGNMENT_BATCH_RETURNED,
            'Batch phan cong bi mo lai do nghi le',
            'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh')
                . ' da bi mo lai ve trang thai nhap do phat sinh ngay nghi "' . $holidayName . '". Vui long phan cong lai cac tiet con thieu va gui duyet.',
            route('department-monthly-assignment-batches.show', $batch->id),
            'assignment-batch-reopened-holiday:' . $batch->id . ':' . now()->timestamp,
            [
                'batch_id' => $batch->id,
                'department_id' => $batch->department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyMonthlyAssignmentDossierSubmitted(MonthlyAssignmentDossier $dossier, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED,
            'Ho so phan cong thang da duoc gui',
            sprintf('Ho so phan cong thang %02d/%d dang cho Lanh dao PDT duyet.', $dossier->month, $dossier->year),
            route('monthly-assignment-dossiers.show', $dossier->id),
            'monthly-assignment-dossier-submitted:' . $dossier->id . ':' . optional($dossier->submitted_at)->timestamp,
            [
                'dossier_id' => $dossier->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyMonthlyAssignmentDossierTrainingOfficeReviewed(MonthlyAssignmentDossier $dossier, User $actor, bool $approved): void
    {
        if ($approved) {
            $this->notifyUsers($this->roleRecipients([User::ROLE_LEADERSHIP, User::ROLE_ADMIN]), $this->makeNotification(
                InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED,
                'Ho so phan cong thang da duoc trinh len Ban Giam hieu',
                sprintf('Ho so phan cong thang %02d/%d da duoc Lanh dao PDT duyet va trinh len Ban Giam hieu.', $dossier->month, $dossier->year),
                route('monthly-assignment-dossiers.show', $dossier->id),
                'monthly-assignment-dossier-forwarded-to-leadership:' . $dossier->id . ':' . now()->timestamp,
                [
                    'dossier_id' => $dossier->id,
                    'actor_id' => $actor->id,
                ]
            ));

            return;
        }

        $recipients = $this->recipientUsers($dossier->submittedBy)
            ->merge($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]));

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED,
            'Ho so phan cong thang bi tu choi',
            sprintf('Ho so phan cong thang %02d/%d da bi Lanh dao PDT tu choi.', $dossier->month, $dossier->year),
            route('monthly-assignment-dossiers.show', $dossier->id),
            'monthly-assignment-dossier-training-office-reviewed:' . $dossier->id . ':' . now()->timestamp,
            [
                'dossier_id' => $dossier->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyMonthlyAssignmentDossierReviewed(MonthlyAssignmentDossier $dossier, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_APPROVED
            : InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED;

        $recipients = $this->recipientUsers($dossier->submittedBy)
            ->merge($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]));

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'Ho so phan cong thang da duoc phe duyet' : 'Ho so phan cong thang bi tu choi',
            $approved
                ? sprintf('Ho so phan cong thang %02d/%d da duoc Ban Giam hieu phe duyet.', $dossier->month, $dossier->year)
                : sprintf('Ho so phan cong thang %02d/%d da bi Ban Giam hieu tu choi.', $dossier->month, $dossier->year),
            route('monthly-assignment-dossiers.show', $dossier->id),
            'monthly-assignment-dossier-reviewed:' . $dossier->id . ':' . now()->timestamp,
            [
                'dossier_id' => $dossier->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * Ho so phan cong thang bi he thong tu dong mo lai ve draft do phat sinh ngay nghi le/tet dot xuat.
     */
    public function notifyMonthlyAssignmentDossierReopenedForHoliday(MonthlyAssignmentDossier $dossier, User $actor, string $holidayName): void
    {
        $recipients = $this->recipientUsers($dossier->submittedBy)
            ->merge($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]));

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED,
            'Ho so phan cong thang bi mo lai do nghi le',
            sprintf(
                'Ho so phan cong thang %02d/%d da bi mo lai ve trang thai nhap do phat sinh ngay nghi "%s". Can cho cac khoa gui lai batch truoc khi tong hop lai.',
                $dossier->month,
                $dossier->year,
                $holidayName
            ),
            route('monthly-assignment-dossiers.show', $dossier->id),
            'monthly-assignment-dossier-reopened-holiday:' . $dossier->id . ':' . now()->timestamp,
            [
                'dossier_id' => $dossier->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportRequestSubmitted(TeachingSupportRequest $request, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::TEACHING_SUPPORT_REQUEST_SUBMITTED,
            'Co de nghi ho tro moi',
            'De nghi ho tro #' . $request->id . ' vua duoc gui len PDT.',
            route('teaching-support-requests.show', $request->id),
            'teaching-support-request-submitted:' . $request->id,
            [
                'request_id' => $request->id,
                'requesting_department_id' => $request->requesting_department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportRequestReviewed(TeachingSupportRequest $request, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::TEACHING_SUPPORT_REQUEST_APPROVED
            : InternalNotificationType::TEACHING_SUPPORT_REQUEST_REJECTED;

        $recipients = $this->departmentStaffRecipients((int) $request->requesting_department_id)
            ->merge($this->recipientUsers($request->submittedBy));

        if ($approved) {
            $recipients = $recipients->merge($this->departmentStaffRecipients((int) ($request->assigned_supporting_department_id ?? 0)));
        }

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'De nghi ho tro da duoc duyet' : 'De nghi ho tro bi tra ve',
            $approved
                ? 'De nghi ho tro #' . $request->id . ' da duoc PDT duyet va giao sang khoa ho tro.'
                : 'De nghi ho tro #' . $request->id . ' da bi PDT tra ve.',
            route('teaching-support-requests.show', $request->id),
            'teaching-support-request-reviewed:' . $request->id . ':' . ($approved ? 'approved' : 'rejected'),
            [
                'request_id' => $request->id,
                'requesting_department_id' => $request->requesting_department_id,
                'assigned_supporting_department_id' => $request->assigned_supporting_department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportRequestWithdrawn(TeachingSupportRequest $request, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::TEACHING_SUPPORT_REQUEST_WITHDRAWN,
            'De nghi ho tro da bi thu hoi',
            'De nghi ho tro #' . $request->id . ' da bi khoa yeu cau thu hoi.',
            route('teaching-support-requests.show', $request->id),
            'teaching-support-request-withdrawn:' . $request->id,
            [
                'request_id' => $request->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportRequestCompleted(TeachingSupportRequest $request, User $actor): void
    {
        $recipients = $this->departmentStaffRecipients((int) $request->requesting_department_id)
            ->merge($this->recipientUsers($request->submittedBy));

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::TEACHING_SUPPORT_REQUEST_COMPLETED,
            'De nghi ho tro da hoan tat',
            'De nghi ho tro #' . $request->id . ' da hoan tat phan cong giang vien.',
            route('teaching-support-requests.show', $request->id),
            'teaching-support-request-completed:' . $request->id,
            [
                'request_id' => $request->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportChangeRequestSubmitted(TeachingSupportChangeRequest $changeRequest, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED,
            'Co phieu huy ho tro moi',
            'Phieu huy ho tro #' . $changeRequest->id . ' vua duoc gui len PDT.',
            route('teaching-support-change-requests.show', $changeRequest->id),
            'teaching-support-change-request-submitted:' . $changeRequest->id,
            [
                'change_request_id' => $changeRequest->id,
                'request_id' => $changeRequest->teaching_support_request_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyTeachingSupportChangeRequestReviewed(TeachingSupportChangeRequest $changeRequest, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED
            : InternalNotificationType::TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED;

        $recipients = $this->departmentStaffRecipients((int) $changeRequest->requesting_department_id)
            ->merge($this->recipientUsers($changeRequest->submittedBy));

        if ($approved) {
            $recipients = $recipients->merge($this->departmentStaffRecipients((int) ($changeRequest->assigned_supporting_department_id ?? 0)));
        }

        $this->notifyUsers($recipients, $this->makeNotification(
            $type,
            $approved ? 'Phieu huy ho tro da duoc duyet' : 'Phieu huy ho tro bi tra ve',
            $approved
                ? 'Phieu huy ho tro #' . $changeRequest->id . ' da duoc PDT duyet va se huy yeu cau ho tro goc.'
                : 'Phieu huy ho tro #' . $changeRequest->id . ' da bi PDT tra ve.',
            route('teaching-support-change-requests.show', $changeRequest->id),
            'teaching-support-change-request-reviewed:' . $changeRequest->id . ':' . ($approved ? 'approved' : 'returned'),
            [
                'change_request_id' => $changeRequest->id,
                'request_id' => $changeRequest->teaching_support_request_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    /**
     * @param array<int, string> $roles slug trong bang `roles` (vd. Role::TRAINING_OFFICE, Role::ADMIN)
     * @return Collection<int, User>
     */
    private function roleRecipients(array $roles): Collection
    {
        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', $roles))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function departmentStaffRecipients(int $departmentId): Collection
    {
        if ($departmentId <= 0) {
            return collect();
        }

        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->where('department_id', $departmentId)
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::DEPARTMENT_STAFF))
            ->orderBy('id')
            ->get();
    }

    /**
     * Lanh dao Khoa (Truong khoa/Bo mon) cua dung mot Khoa.
     *
     * @return Collection<int, User>
     */
    private function departmentLeadershipRecipients(int $departmentId): Collection
    {
        if ($departmentId <= 0) {
            return collect();
        }

        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->where('department_id', $departmentId)
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::DEPARTMENT_HEAD))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientUsers(mixed $user): Collection
    {
        if (! $user instanceof User) {
            return collect();
        }

        return collect([$user]);
    }

    private function makeNotification(
        InternalNotificationType $type,
        string $title,
        string $message,
        ?string $url,
        string $dedupeKey,
        array $meta = []
    ): InternalNotification {
        return new InternalNotification($type, $title, $message, $url, $dedupeKey, $meta);
    }

    private function hasDuplicate(User $user, string $dedupeKey): bool
    {
        return $user->notifications()
            ->where('type', InternalNotification::class)
            ->get()
            ->contains(function ($notification) use ($dedupeKey): bool {
                return (string) data_get($notification->data, 'dedupe_key') === $dedupeKey;
            });
    }
}
