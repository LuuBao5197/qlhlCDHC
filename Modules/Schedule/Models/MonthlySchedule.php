<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySchedule extends Model
{
    protected $table = 'monthly_schedules';

    protected $fillable = [
        'plan_id',
        'class_name',
        'month',
        'year'
    ];

    // Thuộc về Plan
    public function plan()
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    // 1 tháng có nhiều buổi học
    public function scheduleSlots()
    {
        return $this->hasMany(ScheduleSlot::class, 'monthly_schedule_id');
    }
}
