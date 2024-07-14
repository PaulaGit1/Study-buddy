<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorSession extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'tutor_id','subject_id', 'session_time', 'status'];

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'session_id');
    }
}
