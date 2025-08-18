<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'type',
        'description',
        'image_url',
        'video_url'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

   public function charges()
{
    return $this->belongsToMany(
        Charge::class,       // related model
        'machine_charges',   // pivot table
        'machine_id',        // foreign key for Machine
        'charge_id'          // foreign key for Charge
    )->withTimestamps();
}


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'machine_categories');
    }

    public function machineCharges()
    {
        return $this->hasMany(MachineCharge::class);
    }

    public function machineCategories()
    {
        return $this->hasMany(MachineCategory::class);
    }
}