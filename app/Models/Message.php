<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)
            ->withDefault([
                'name' => _('User')
            ]);
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'recipients', 'message_id', 'user_id')
            ->withPivot([
                'read_at', 'deleted_at'
            ]);
    }
}
