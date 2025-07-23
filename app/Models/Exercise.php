<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_id',
        'machine_id',
        'name',
        'sets',
        'reps',
        'charge_id',
        'instructions',
        'title'
    ];

    protected $casts = [
        'sets' => 'integer',
        'reps' => 'integer',
    ];

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function workoutExercises()
    {
        return $this->hasMany(WorkoutExercise::class);
    }

 // In Exercise.php model
public function workouts()
{
    return $this->belongsToMany(Workout::class)
        ->withPivot(['achievement', 'is_done', 'order'])
        ->withTimestamps();
}
}