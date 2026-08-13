<?php
namespace Modules\Schedule\Models;
use App\Models\AdminBackfillLog;
use App\Models\User;
use Modules\Training\Models\ApprovalRequest;
use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\MonthlyReport;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MonthlySchedule extends Model
{
    protected $table = 'monthly_schedules';

    protected $fillable = [
        'plan_id',
        'month',
        'year',
        'created_by',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'monthly_schedule_id');
    }

    public function scheduleSlotGroups(): HasMany
    {
        return $this->hasMany(ScheduleSlotGroup::class, 'monthly_schedule_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'entity', 'entity_type', 'entity_id');
    }

    public function adminBackfillLog(): MorphOne
    {
        return $this->morphOne(AdminBackfillLog::class, 'loggable');
    }
}
