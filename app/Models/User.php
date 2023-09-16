<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'participants', 'user_id','conversation_id')
            ->latest('last_message_id')
            ->withPivot([
                'joined_at', 'role'
            ]);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'user_id', 'id');
    }

    public function receivedMessages()
    {
        return $this->belongsToMany(Message::class, 'recipients', 'user_id', 'message_id')
            ->withPivot([
                'read_at', 'deleted_at'
            ]);
    }
}
