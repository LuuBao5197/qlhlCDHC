<?php

namespace App\Services;

use App\Enums\InternalNotificationType;
use App\Models\User;
use App\Notifications\InternalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
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

    public function notifyApprovedUserRegistration(User $user, User $actor): void
    {
        $this->notifyUsers([$user], $this->makeNotification(
            InternalNotificationType::USER_REGISTRATION_APPROVED,
            'Tai khoan da duoc phe duyet',
            'Tai khoan ' . $user->name . ' da duoc phe duyet va co the dang nhap.',
            route('settings'),
            'user-registration-approved:' . $user->id,
            [
                'user_id' => $user->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyRejectedUserRegistration(User $user, User $actor): void
    {
        $this->notifyUsers([$user], $this->makeNotification(
            InternalNotificationType::USER_REGISTRATION_REJECTED,
            'Tai khoan da bi tu choi',
            'Tai khoan ' . $user->name . ' da bi tu choi trong qua trinh xet duyet.',
            route('settings'),
            'user-registration-rejected:' . $user->id,
            [
                'user_id' => $user->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyPendingUserRegistration(User $user): void
    {
        $recipients = $this->roleRecipients([User::ROLE_ADMIN]);

        $this->notifyUsers($recipients, $this->makeNotification(
            InternalNotificationType::USER_REGISTRATION_PENDING,
            'Co tai khoan dang ky moi',
            'Tai khoan ' . $user->name . ' vua gui yeu cau dang ky va can duoc xem xet.',
            route('admin.index'),
            'user-registration-pending:' . $user->id,
            [
                'user_id' => $user->id,
            ]
        ));
    }

    public function notifySemesterPlanSubmitted(Plans $plan, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_LEADERSHIP, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::SEMESTER_PLAN_SUBMITTED,
            'Ke hoach hoc ky da duoc gui',
            $plan->name . ' vua duoc gui len cap phe duyet.',
            route('schedule.show', $plan->id),
            'semester-plan-submitted:' . $plan->id,
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
            route('schedule.show', $changeRequest->monthly_schedule_id),
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
            route('schedule.show', $changeRequest->monthly_schedule_id),
            'schedule-change-request-reviewed:' . $changeRequest->id . ':' . ($approved ? 'approved' : 'rejected'),
            [
                'change_request_id' => $changeRequest->id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyDepartmentMonthlyAssignmentBatchSubmitted(DepartmentMonthlyAssignmentBatch $batch, User $actor): void
    {
        $this->notifyUsers($this->roleRecipients([User::ROLE_TRAINING_OFFICE, User::ROLE_ADMIN]), $this->makeNotification(
            InternalNotificationType::ASSIGNMENT_BATCH_SUBMITTED,
            'Batch phan cong da duoc gui',
            'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' dang cho PDT duyet.',
            route('department-monthly-assignment-batches.show', $batch->id),
            'assignment-batch-submitted:' . $batch->id,
            [
                'batch_id' => $batch->id,
                'department_id' => $batch->department_id,
                'actor_id' => $actor->id,
            ]
        ));
    }

    public function notifyDepartmentMonthlyAssignmentBatchReviewed(DepartmentMonthlyAssignmentBatch $batch, User $actor, bool $approved): void
    {
        $type = $approved
            ? InternalNotificationType::ASSIGNMENT_BATCH_APPROVED
            : InternalNotificationType::ASSIGNMENT_BATCH_RETURNED;

        $message = $approved
            ? 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da duoc duyet.'
            : 'Batch phan cong cua khoa ' . ($batch->department?->name ?? 'khong xac dinh') . ' da bi tra ve.';

        $this->notifyUsers($this->departmentStaffRecipients((int) $batch->department_id)->merge($this->recipientUsers($batch->submittedBy)), $this->makeNotification(
            $type,
            $approved ? 'Batch phan cong da duoc duyet' : 'Batch phan cong bi tra ve',
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
     * @param array<int, string> $roles
     * @return Collection<int, User>
     */
    private function roleRecipients(array $roles): Collection
    {
        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->whereIn('role', $roles)
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
            ->where('role', User::ROLE_DEPARTMENT_STAFF)
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
