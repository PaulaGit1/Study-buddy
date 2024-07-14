<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\UserNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_info',
        'role',
        'status',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }

    public function unreadNotificationsCount()
    {
        return $this->userNotifications()->where('is_read', false)->count();
    }

    public function tutorSessions()
    {
        return $this->hasMany(TutorSession::class, 'tutor_id');
        
    }

    public function studentSessions()
    {
        return $this->hasMany(TutorSession::class, 'student_id');
        
    }

    public function feedbacks()
    {
        return $this->hasManyThrough(Feedback::class, TutorSession::class, 'tutor_id', 'session_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'tutor_subjects', 'tutor_id', 'subject_id');
    
    }

    public function availabilities()
    {
        return $this->hasMany(TutorAvailability::class, 'tutor_id');
    }
}
