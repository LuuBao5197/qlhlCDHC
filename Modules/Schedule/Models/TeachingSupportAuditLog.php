<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Training\Models\Department;

class TeachingSupportAuditLog extends Model
{
    protected $table = 'teaching_support_audit_logs';

    protected $fillable = [
        'request_id',
        'request_item_id',
        'schedule_slot_id',
        'change_request_id',
        'action',
        'actor_user_id',
        'actor_name_snapshot',
        'actor_role_snapshot',
        'actor_department_id',
        'actor_department_name_snapshot',
        'old_values',
        'new_values',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequest::class, 'request_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequestItem::class, 'request_item_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportChangeRequest::class, 'change_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'actor_department_id');
    }
}
