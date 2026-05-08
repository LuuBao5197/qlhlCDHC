<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDailySummary extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\TeacherDailySummaryFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'log_date',
        'training_plan_comment',
        'regulation_comment',
        'facility_comment',
        'followup_comment',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
