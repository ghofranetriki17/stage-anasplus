<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'day_of_week',
        'opening_hour',
        'closing_hour',
        'is_closed'
    ];

    protected $casts = [
        'opening_hour' => 'datetime:H:i',
        'closing_hour' => 'datetime:H:i',
        'is_closed' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}