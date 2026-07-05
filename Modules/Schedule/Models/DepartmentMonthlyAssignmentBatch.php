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
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'version',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
