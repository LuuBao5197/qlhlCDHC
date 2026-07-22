<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Training\Models\Department;

class DepartmentMonthlyAssignmentBatch extends Model
{
    protected $table = 'department_monthly_assignment_batches';

    protected $fillable = [
        'department_id',
        'month',
        'year',
        'status',
        'current_step',
        'submitted_by',
        'submitted_at',
        'department_reviewed_by',
        'department_reviewed_at',
        'department_review_note',
        'training_office_reviewed_by',
        'training_office_reviewed_at',
        'training_office_review_note',
        'version',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'department_reviewed_at' => 'datetime',
        'training_office_reviewed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';

    public const STEP_DRAFT = 'draft';
    public const STEP_DEPARTMENT_REVIEW = 'department_review';
    public const STEP_TRAINING_OFFICE_REVIEW = 'training_office_review';
    public const STEP_COMPLETED = 'completed';

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function departmentReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_reviewed_by');
    }

    public function trainingOfficeReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'training_office_reviewed_by');
    }

    public function batchSlots(): HasMany
    {
        return $this->hasMany(DepartmentMonthlyAssignmentBatchSlot::class, 'batch_id');
    }

    public function scheduleSlots(): BelongsToMany
    {
        return $this->belongsToMany(
            ScheduleSlot::class,
            'department_monthly_assignment_batch_slots',
            'batch_id',
            'schedule_slot_id'
        )->withTimestamps();
    }

    public function teachingSupportRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportRequest::class, 'assignment_batch_id');
    }
}
