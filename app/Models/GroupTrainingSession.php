<?php
// Model: GroupTrainingSession.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupTrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'coach_id',
        'course_id',
        'session_date',
        'duration',
        'title',
        'is_for_women',
        'is_free',
        'is_for_kids',
        'max_participants',
        'current_participants'
    ];

    protected $casts = [
        'session_date' => 'datetime',
        'is_for_women' => 'boolean',
        'is_free' => 'boolean',
        'is_for_kids' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function isFullyBooked()
    {
        return $this->max_participants && $this->current_participants >= $this->max_participants;
    }

    public function hasAvailableSpots()
    {
        return !$this->max_participants || $this->current_participants < $this->max_participants;
    }
    // GroupTrainingSession.php
public function users()
{
    return $this->belongsToMany(User::class)
        ->withTimestamps()
        ->withPivot('booked_at');
}


}