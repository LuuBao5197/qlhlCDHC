<?php

namespace App\Enums;

enum InternalNotificationType: string
{
    case USER_REGISTRATION_PENDING = 'user_registration_pending';
    case USER_REGISTRATION_APPROVED = 'user_registration_approved';
    case USER_REGISTRATION_REJECTED = 'user_registration_rejected';

    case PASSWORD_RESET_REQUESTED = 'password_reset_requested';

    case SEMESTER_PLAN_SUBMITTED = 'semester_plan_submitted';
    case SEMESTER_PLAN_APPROVED = 'semester_plan_approved';
    case SEMESTER_PLAN_REJECTED = 'semester_plan_rejected';

    case SCHEDULE_CHANGE_REQUEST_SUBMITTED = 'schedule_change_request_submitted';
    case SCHEDULE_CHANGE_REQUEST_APPROVED = 'schedule_change_request_approved';
    case SCHEDULE_CHANGE_REQUEST_REJECTED = 'schedule_change_request_rejected';

    case ASSIGNMENT_BATCH_SUBMITTED = 'assignment_batch_submitted';
    case ASSIGNMENT_BATCH_APPROVED = 'assignment_batch_approved';
    case ASSIGNMENT_BATCH_RETURNED = 'assignment_batch_returned';
    case ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED = 'assignment_batch_training_office_approved';
    case ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED = 'assignment_batch_training_office_returned';

    case MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED = 'monthly_assignment_dossier_submitted';
    case MONTHLY_ASSIGNMENT_DOSSIER_APPROVED = 'monthly_assignment_dossier_approved';
    case MONTHLY_ASSIGNMENT_DOSSIER_REJECTED = 'monthly_assignment_dossier_rejected';

    case TEACHING_SUPPORT_REQUEST_SUBMITTED = 'teaching_support_request_submitted';
    case TEACHING_SUPPORT_REQUEST_APPROVED = 'teaching_support_request_approved';
    case TEACHING_SUPPORT_REQUEST_REJECTED = 'teaching_support_request_rejected';
    case TEACHING_SUPPORT_REQUEST_WITHDRAWN = 'teaching_support_request_withdrawn';
    case TEACHING_SUPPORT_REQUEST_COMPLETED = 'teaching_support_request_completed';

    case TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED = 'teaching_support_change_request_submitted';
    case TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED = 'teaching_support_change_request_approved';
    case TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED = 'teaching_support_change_request_returned';

    public function label(): string
    {
        return match ($this) {
            self::USER_REGISTRATION_PENDING => 'Dang ky moi',
            self::USER_REGISTRATION_APPROVED => 'Dang ky da duyet',
            self::USER_REGISTRATION_REJECTED => 'Dang ky bi tu choi',
            self::PASSWORD_RESET_REQUESTED => 'Yeu cau dat lai mat khau',
            self::SEMESTER_PLAN_SUBMITTED => 'Ke hoach hoc ky',
            self::SEMESTER_PLAN_APPROVED => 'Ke hoach hoc ky da duyet',
            self::SEMESTER_PLAN_REJECTED => 'Ke hoach hoc ky bi tu choi',
            self::SCHEDULE_CHANGE_REQUEST_SUBMITTED => 'Phieu thay doi lich',
            self::SCHEDULE_CHANGE_REQUEST_APPROVED => 'Phieu thay doi da duyet',
            self::SCHEDULE_CHANGE_REQUEST_REJECTED => 'Phieu thay doi bi tu choi',
            self::ASSIGNMENT_BATCH_SUBMITTED => 'Batch phan cong',
            self::ASSIGNMENT_BATCH_APPROVED => 'Batch phan cong da duyet',
            self::ASSIGNMENT_BATCH_RETURNED => 'Batch phan cong tra ve',
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED => 'Batch phan cong da duoc PDT duyet',
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED => 'Batch phan cong bi PDT tra ve',
            self::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED => 'Ho so phan cong thang tong hop',
            self::MONTHLY_ASSIGNMENT_DOSSIER_APPROVED => 'Ho so phan cong thang da duyet',
            self::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED => 'Ho so phan cong thang bi tu choi',
            self::TEACHING_SUPPORT_REQUEST_SUBMITTED => 'De nghi ho tro',
            self::TEACHING_SUPPORT_REQUEST_APPROVED => 'De nghi ho tro da duyet',
            self::TEACHING_SUPPORT_REQUEST_REJECTED => 'De nghi ho tro bi tra ve',
            self::TEACHING_SUPPORT_REQUEST_WITHDRAWN => 'De nghi ho tro da thu hoi',
            self::TEACHING_SUPPORT_REQUEST_COMPLETED => 'De nghi ho tro hoan tat',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED => 'Phieu huy ho tro',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED => 'Phieu huy ho tro da duyet',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED => 'Phieu huy ho tro tra ve',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::USER_REGISTRATION_PENDING => 'mdi-account-clock',
            self::USER_REGISTRATION_APPROVED => 'mdi-account-check',
            self::USER_REGISTRATION_REJECTED => 'mdi-account-cancel',
            self::PASSWORD_RESET_REQUESTED => 'mdi-lock-reset',
            self::SEMESTER_PLAN_SUBMITTED => 'mdi-book-open-page-variant',
            self::SEMESTER_PLAN_APPROVED => 'mdi-book-check',
            self::SEMESTER_PLAN_REJECTED => 'mdi-book-remove',
            self::SCHEDULE_CHANGE_REQUEST_SUBMITTED => 'mdi-calendar-edit',
            self::SCHEDULE_CHANGE_REQUEST_APPROVED => 'mdi-calendar-check',
            self::SCHEDULE_CHANGE_REQUEST_REJECTED => 'mdi-calendar-remove',
            self::ASSIGNMENT_BATCH_SUBMITTED => 'mdi-file-send',
            self::ASSIGNMENT_BATCH_APPROVED => 'mdi-check-circle',
            self::ASSIGNMENT_BATCH_RETURNED => 'mdi-undo-variant',
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED => 'mdi-check-circle-outline',
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED => 'mdi-undo-variant',
            self::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED => 'mdi-file-document-multiple',
            self::MONTHLY_ASSIGNMENT_DOSSIER_APPROVED => 'mdi-check-circle',
            self::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED => 'mdi-close-circle',
            self::TEACHING_SUPPORT_REQUEST_SUBMITTED => 'mdi-account-group',
            self::TEACHING_SUPPORT_REQUEST_APPROVED => 'mdi-domain',
            self::TEACHING_SUPPORT_REQUEST_REJECTED => 'mdi-backup-restore',
            self::TEACHING_SUPPORT_REQUEST_WITHDRAWN => 'mdi-cancel',
            self::TEACHING_SUPPORT_REQUEST_COMPLETED => 'mdi-account-multiple-check',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED => 'mdi-file-cancel',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED => 'mdi-check-circle-outline',
            self::TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED => 'mdi-reply',
        };
    }

    public function iconColorClass(): string
    {
        return match ($this) {
            self::USER_REGISTRATION_REJECTED,
            self::SEMESTER_PLAN_REJECTED,
            self::SCHEDULE_CHANGE_REQUEST_REJECTED,
            self::TEACHING_SUPPORT_REQUEST_REJECTED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED => 'text-danger',

            self::ASSIGNMENT_BATCH_APPROVED,
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED,
            self::SEMESTER_PLAN_APPROVED,
            self::TEACHING_SUPPORT_REQUEST_COMPLETED,
            self::TEACHING_SUPPORT_REQUEST_APPROVED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_APPROVED,
            self::USER_REGISTRATION_APPROVED => 'text-success',

            self::ASSIGNMENT_BATCH_RETURNED,
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED,
            self::TEACHING_SUPPORT_REQUEST_WITHDRAWN,
            self::USER_REGISTRATION_PENDING,
            self::PASSWORD_RESET_REQUESTED,
            self::SCHEDULE_CHANGE_REQUEST_SUBMITTED,
            self::TEACHING_SUPPORT_REQUEST_SUBMITTED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED => 'text-warning',

            default => 'text-info',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::USER_REGISTRATION_REJECTED,
            self::SEMESTER_PLAN_REJECTED,
            self::SCHEDULE_CHANGE_REQUEST_REJECTED,
            self::TEACHING_SUPPORT_REQUEST_REJECTED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_RETURNED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_REJECTED => 'badge-danger',

            self::ASSIGNMENT_BATCH_APPROVED,
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_APPROVED,
            self::SEMESTER_PLAN_APPROVED,
            self::TEACHING_SUPPORT_REQUEST_COMPLETED,
            self::TEACHING_SUPPORT_REQUEST_APPROVED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_APPROVED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_APPROVED,
            self::USER_REGISTRATION_APPROVED => 'badge-success',

            self::ASSIGNMENT_BATCH_RETURNED,
            self::ASSIGNMENT_BATCH_TRAINING_OFFICE_RETURNED,
            self::TEACHING_SUPPORT_REQUEST_WITHDRAWN,
            self::USER_REGISTRATION_PENDING,
            self::PASSWORD_RESET_REQUESTED,
            self::SCHEDULE_CHANGE_REQUEST_SUBMITTED,
            self::TEACHING_SUPPORT_REQUEST_SUBMITTED,
            self::TEACHING_SUPPORT_CHANGE_REQUEST_SUBMITTED,
            self::MONTHLY_ASSIGNMENT_DOSSIER_SUBMITTED => 'badge-warning',

            default => 'badge-info',
        };
    }
}
