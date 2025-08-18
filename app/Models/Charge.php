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
    return $this->belongsToMany(
        Machine::class,      // related model
        'machine_charges',   // pivot table
        'charge_id',         // foreign key for Charge
        'machine_id'         // foreign key for Machine
    )->withTimestamps();
}


    public function machineCharges()
    {
        return $this->hasMany(MachineCharge::class);
    }
}