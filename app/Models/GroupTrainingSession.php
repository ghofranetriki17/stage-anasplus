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

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_session_bookings')
            ->withTimestamps()
            ->withPivot('booked_at');
    }

    // Helper methods
    public function getCurrentParticipantsCount()
    {
        return $this->users()->count();
    }

    public function isFullyBooked()
    {
        if (!$this->max_participants) {
            return false; // Unlimited capacity
        }
        return $this->getCurrentParticipantsCount() >= $this->max_participants;
    }

    public function hasAvailableSpots()
    {
        return !$this->isFullyBooked();
    }

    public function getAvailableSpots()
    {
        if (!$this->max_participants) {
            return null; // Unlimited capacity
        }
        return $this->max_participants - $this->getCurrentParticipantsCount();
    }

    public function isUserBooked($userId)
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    // Scope for upcoming sessions
    public function scopeUpcoming($query)
    {
        return $query->where('session_date', '>', now());
    }

    // Scope for available sessions
    public function scopeAvailable($query)
    {
        return $query->whereRaw('
            max_participants IS NULL 
            OR max_participants > (
                SELECT COUNT(*) 
                FROM group_session_bookings 
                WHERE group_training_session_id = group_training_sessions.id
            )
        ');
    }
}