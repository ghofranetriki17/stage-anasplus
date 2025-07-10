<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'charge_id'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }
}