<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActRequestSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'requested_act_id',
        'emitter_administration_id',
        'direction_code',
        'requested_document_name',
        'applicant_full_name',
        'applicant_email',
        'applicant_phone',
        'applicant_payload',
        'attachments',
        'status',
    ];

    protected $casts = [
        'applicant_payload' => 'array',
        'attachments' => 'array',
    ];

    public function requestedAct()
    {
        return $this->belongsTo(RequestedAct::class, 'requested_act_id');
    }

    public function administration()
    {
        return $this->belongsTo(IssuingAdministration::class, 'emitter_administration_id');
    }
}
