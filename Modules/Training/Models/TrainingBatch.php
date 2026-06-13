<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\Plans;

class TrainingBatch extends Model
{
    use HasFactory;

    protected $table = 'training_batches';

    protected $fillable = [
        'training_program_id',
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(TrainingClass::class, 'training_batch_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plans::class, 'training_batch_id');
    }
}
