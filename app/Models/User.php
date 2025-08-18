<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // OK on Laravel 10/11
    ];

    public function progresses()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function groupSessions()
    {
        return $this->belongsToMany(GroupTrainingSession::class, 'group_session_bookings')
            ->withTimestamps()
            ->withPivot('booked_at');
    }
}
