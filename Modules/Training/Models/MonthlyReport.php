<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Schedule\Models\MonthlySchedule;

class MonthlyReport extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\MonthlyReportFactory> */
    use HasFactory;

    protected $fillable = [
        'monthly_schedule_id',
        'created_by',
        'summary',
        'result_overview',
        'recommendation',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function monthlySchedule(): BelongsTo
    {
        return $this->belongsTo(MonthlySchedule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
