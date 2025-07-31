<?php
// Model: Speciality.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speciality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function coaches()
    {
        return $this->belongsToMany(Coach::class, 'coach_specialities');
    }
}