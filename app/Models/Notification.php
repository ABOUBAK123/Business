<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['recipient_id', 'title', 'message', 'type', 'workflow_id', 'execution_id', 'action_url', 'is_read'];
    protected $casts = ['is_read' => 'boolean', 'created_at' => 'datetime'];

    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
}
