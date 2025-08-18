<?php
// Model: Movement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use HasFactory;

  protected $fillable = [
        'name','description','video_url',
        'media_url','media_type',
    ];


    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }
}