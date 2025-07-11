<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeWorkout extends Model
{
    use HasFactory;

    protected $fillable = [
        'programme_id',
        'workout_id',
        'order',
        'week_day'
    ];

    protected $casts = [
        'order' => 'integer',
        'week_day' => 'integer', // 1-7 (Monday to Sunday)
    ];

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
