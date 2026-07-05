<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Training\Models\Department;

class TeachingSupportRequest extends Model
{
    public const STATUS_PENDING_PDT = 'pending_pdt';
    public const STATUS_ASSIGNED_TO_DEPARTMENT = 'assigned_to_department';
    public const STATUS_DEPARTMENT_ASSIGNING = 'department_assigning';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'teaching_support_requests';

    protected $fillable = [
        'assignment_batch_id',
        'requesting_department_id',
        'proposed_supporting_department_id',
        'assigned_supporting_department_id',
        'status',
        'request_note',
        'submitted_by',
        'submitted_at',
        'pdt_processed_by',
        'pdt_processed_at',
        'pdt_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'pdt_processed_at' => 'datetime',
    ];

    public function assignmentBatch(): BelongsTo
    {
        return $this->belongsTo(DepartmentMonthlyAssignmentBatch::class, 'assignment_batch_id');
    }

    public function requestingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requesting_department_id');
    }

    public function proposedSupportingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'proposed_supporting_department_id');
    }

    public function assignedSupportingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_supporting_department_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function pdtProcessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pdt_processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TeachingSupportRequestItem::class, 'request_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportChangeRequest::class, 'teaching_support_request_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TeachingSupportAuditLog::class, 'request_id');
    }
}
