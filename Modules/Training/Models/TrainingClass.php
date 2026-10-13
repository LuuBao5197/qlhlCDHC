<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;

class TrainingClass extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\TrainingClassFactory> */
    use HasFactory;

    protected $table = 'classes';

    protected $appends = ['account_email'];

    protected $fillable = [
        'code',
        'name',
        'course_year',
        'total_students',
        'default_room_id',
        'training_batch_id',
        'status',
    ];

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function getAccountEmailAttribute(): ?string
    {
        // Chi tra ve khi relation da duoc eager load (man hinh quan ly lop), tranh N+1 va lo email o noi khac.
        return $this->relationLoaded('accountUser') ? $this->accountUser?->email : null;
    }

    public function defaultRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'default_room_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function monthlySchedules(): HasMany
    {
        return $this->hasMany(MonthlySchedule::class, 'class_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'class_id');
    }
}
