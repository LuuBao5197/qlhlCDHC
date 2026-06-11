<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'is_active',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
}
