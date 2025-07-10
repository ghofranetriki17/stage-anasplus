<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = [
        'weight'
    ];

    protected $casts = [
        'weight' => 'decimal:2'
    ];

    public function machines()
    {
        return $this->belongsToMany(Machine::class, 'machine_charges');
    }

    public function machineCharges()
    {
        return $this->hasMany(MachineCharge::class);
    }
}