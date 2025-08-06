<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'coach_id',
        'title',
        'description',
        'video_url',
    ];

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}
