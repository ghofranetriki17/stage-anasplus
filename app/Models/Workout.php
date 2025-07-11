<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notes',
        'water_consumption',
        'duration',
        'is_rest_day',
        'title'
    ];

    protected $casts = [
        'water_consumption' => 'decimal:2',
        'duration' => 'integer', // in minutes
        'is_rest_day' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'workout_exercises')
                    ->withPivot('achievement', 'is_done', 'order')
                    ->withTimestamps();
    }

    public function workoutExercises()
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function programmes()
    {
        return $this->belongsToMany(Programme::class, 'programme_workouts')
                    ->withPivot('order', 'week_day')
                    ->withTimestamps();
    }

    public function programmeWorkouts()
    {
        return $this->hasMany(ProgrammeWorkout::class);
    }
}