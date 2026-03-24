<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    protected $table = 'schedule_slots';

    protected $fillable = [
        'monthly_schedule_id',
        'date',
        'day_of_week',
        'period',
        'period_number',
        'subject',
        'content'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    // Thuộc về MonthlySchedule
    public function monthlySchedule()
    {
        return $this->belongsTo(MonthlySchedule::class, 'monthly_schedule_id');
    }
}
