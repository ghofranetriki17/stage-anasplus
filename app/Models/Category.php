<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function machines()
    {
        return $this->belongsToMany(Machine::class, 'machine_categories');
    }

    public function machineCategories()
    {
        return $this->hasMany(MachineCategory::class);
    }
}