<?php

namespace App\Models\Base;

use App\Enums\DesireType;
use App\Enums\HabitNature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'habits';

    protected $primaryKey = 'habit_id';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
        'habit_nature',
        'desire_type',
        'implementation_intention',
        'location',
        'cue',
        'reframe',
        'is_active',
        'needs_occurrence_rebuild',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_occurrence_rebuild' => 'boolean',
        'habit_nature' => HabitNature::class,
        'desire_type' => DesireType::class,
    ];
}
