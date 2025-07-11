<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'objectif',
        'description',
        'duration_weeks',
        'is_active'
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workouts()
    {
        return $this->belongsToMany(Workout::class, 'programme_workouts')
                    ->withPivot('order', 'week_day')
                    ->withTimestamps();
    }

    public function programmeWorkouts()
    {
        return $this->hasMany(ProgrammeWorkout::class);
    }
}