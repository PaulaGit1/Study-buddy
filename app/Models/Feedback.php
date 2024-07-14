<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    
    protected $fillable = ['session_id', 'rating', 'comments'];

    public function session()
    {
        return $this->belongsTo(TutorSession::class, 'session_id');
    }
}
