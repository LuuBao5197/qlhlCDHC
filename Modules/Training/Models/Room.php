<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\ScheduleSlot;

class Room extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'capacity',
        'room_type',
        'status',
    ];

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
