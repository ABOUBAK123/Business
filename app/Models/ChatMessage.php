<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['sender_id', 'sender_name', 'sender_initials', 'recipient_id', 'text', 'room', 'type'];
    protected $casts = ['created_at' => 'datetime'];

    public function sender() {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }
}
