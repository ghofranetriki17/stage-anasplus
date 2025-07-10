<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'category_id'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}