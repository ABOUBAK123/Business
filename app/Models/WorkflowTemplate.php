<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    use HasUuids;

    protected $fillable = ['administration_id', 'name', 'description', 'validation_steps', 'signature_steps', 'notification_config', 'status', 'created_by'];
    protected $casts = ['validation_steps' => 'array', 'signature_steps' => 'array', 'notification_config' => 'array'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
