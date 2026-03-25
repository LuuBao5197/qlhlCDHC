<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'semester',
        'year',
        'file_path',
        'description'
    ];

    // 1 Plan có nhiều lịch tháng
    public function monthlySchedules()
    {
        return $this->hasMany(MonthlySchedule::class, 'plan_id');
    }
}
