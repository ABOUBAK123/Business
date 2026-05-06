<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowExecution extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['workflow_id', 'document_id', 'platform_workflow_id', 'platform_status', 'current_step', 'status', 'step_data', 'started_at', 'completed_at'];
    protected $casts = ['step_data' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function workflow() { return $this->belongsTo(Workflow::class); }
    public function document() { return $this->belongsTo(Document::class)->withTrashed(); }
}
