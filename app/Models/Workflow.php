<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'description', 'status', 'docs_to_sign', 'attached_docs', 'uploaded_signature_files', 'created_by'];
    protected $casts = ['docs_to_sign' => 'array', 'attached_docs' => 'array', 'uploaded_signature_files' => 'array'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function steps() { return $this->hasMany(WorkflowStep::class)->orderBy('order'); }
    public function executions() { return $this->hasMany(WorkflowExecution::class); }
    public function template() { return $this->belongsTo(WorkflowTemplate::class); }
}
