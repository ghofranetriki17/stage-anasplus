<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address', 
        'city',
        'phone',
        'email'
    ];

    public function availabilities()
    {
        return $this->hasMany(BranchAvailability::class);
    }

    public function getAvailabilityForDay($dayOfWeek)
    {
        return $this->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }
}