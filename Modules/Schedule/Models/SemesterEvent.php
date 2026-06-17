<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Training\Models\TrainingClass;

class SemesterEvent extends Model
{
    protected $table = 'semester_events';

    protected $fillable = [
        'plan_id',
        'class_id',
        'event_type',
        'title',
        'start_date',
        'end_date',
        'period_from',
        'period_to',
        'color',
        'note',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'period_from' => 'integer',
        'period_to' => 'integer',
        'sort_order' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }
}
