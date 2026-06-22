<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentMonthlyAssignmentBatchSlot extends Model
{
    protected $table = 'department_monthly_assignment_batch_slots';

    protected $fillable = [
        'batch_id',
        'schedule_slot_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DepartmentMonthlyAssignmentBatch::class, 'batch_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }
}
