<?php
// Model: Coach.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    use HasFactory;

  protected $fillable = [
    'name',
    'email',
    'phone',
    'photo_url', // <-- ajoute ici
    'hourly_rate_online',
    'hourly_rate_presential',
    'bio',
    'certifications',
    'rating',
    'total_sessions',
    'total_earnings',
    'is_available',
    'branch_id'
];

    protected $casts = [
        'hourly_rate_online' => 'decimal:2',
        'hourly_rate_presential' => 'decimal:2',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'is_available' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function availabilities()
    {
        return $this->hasMany(CoachAvailability::class);
    }

    public function groupTrainingSessions()
    {
        return $this->hasMany(GroupTrainingSession::class);
    }

    public function specialities()
    {
        return $this->belongsToMany(Speciality::class, 'coach_specialities');
    }

    public function getAvailabilityForDay($dayOfWeek)
    {
        return $this->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->get();
    }
}

