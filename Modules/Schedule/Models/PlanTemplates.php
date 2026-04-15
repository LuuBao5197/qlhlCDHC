<?php

namespace Modules\Schedule\Models;

use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class PlanTemplates extends Model
{
    protected $table = 'plan_templates';

    protected $fillable = [
        'plan_id',
        'class_id',
        'subject_id',
        'day_of_week',
        'days_of_week',
        'session',
        'period_range',
        'description',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'days_of_week' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    public function classes()
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function subjects(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

}
