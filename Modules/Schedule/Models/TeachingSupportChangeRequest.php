<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Training\Models\Department;

class TeachingSupportChangeRequest extends Model
{
    public const STATUS_PENDING_PDT = 'pending_pdt';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'teaching_support_change_requests';

    protected $fillable = [
        'teaching_support_request_id',
        'requesting_department_id',
        'proposed_supporting_department_id',
        'assigned_supporting_department_id',
        'status',
        'reason',
        'submitted_by',
        'submitted_at',
        'pdt_processed_by',
        'pdt_processed_at',
        'pdt_note',
        'revision_no',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'pdt_processed_at' => 'datetime',
    ];

    public function teachingSupportRequest(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequest::class, 'teaching_support_request_id');
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
        return $this->hasMany(TeachingSupportChangeRequestItem::class, 'change_request_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TeachingSupportAuditLog::class, 'change_request_id');
    }
}
