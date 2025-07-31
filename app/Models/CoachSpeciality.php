<?php
// Model: CoachSpeciality.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachSpeciality extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'speciality_id'
    ];

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
}

