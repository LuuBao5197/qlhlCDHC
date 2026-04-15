<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function trainingClasses(): HasMany
    {
        return $this->hasMany(TrainingClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}