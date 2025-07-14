<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    // Explicitly set the table name
    protected $table = 'user_progresses';

    protected $fillable = [
        'user_id',
        'weight',
        'height',
        'body_fat',
        'muscle_mass',
        'imc',
        'recorded_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}