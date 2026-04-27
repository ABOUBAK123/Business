<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['workflow_id', 'order', 'name', 'type', 'assignee_id', 'description', 'requires_signature'];
    protected $casts = ['requires_signature' => 'boolean', 'order' => 'integer', 'created_at' => 'datetime'];

    public function workflow() { return $this->belongsTo(Workflow::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
}
