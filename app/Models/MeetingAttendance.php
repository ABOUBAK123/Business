<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MeetingAttendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'meeting_id',
        'meeting_participant_id',
        'identifier',
        'full_name',
        'email',
        'phone',
        'job_title',
        'organization',
        'attendance_status',
        'signed_at',
        'signature_path',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function participant()
    {
        return $this->belongsTo(MeetingParticipant::class, 'meeting_participant_id');
    }
}
