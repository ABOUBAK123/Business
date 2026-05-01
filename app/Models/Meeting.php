<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'meeting_type',
        'meeting_room_id',
        'starts_at',
        'ends_at',
        'processing_deadline',
        'estimated_duration_minutes',
        'organizer_id',
        'minutes_writer_id',
        'issuing_administration_id',
        'sub_entity_code',
        'agenda',
        'minutes_template',
        'minutes_content',
        'attachments',
        'priority',
        'confidentiality',
        'status',
        'workflow_status',
        'review_requested',
        'review_comment',
        'writer_signature_path',
        'writer_signed_at',
        'published_at',
        'diffusion_email_subject',
        'diffusion_email_body',
        'diffusion_ack_required',
        'recurrence_type',
        'recurrence_until',
        'recurrence_exceptions',
        'qr_token',
        'qr_valid_from',
        'qr_valid_until',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'processing_deadline' => 'datetime',
        'recurrence_until' => 'date',
        'attachments' => 'array',
        'recurrence_exceptions' => 'array',
        'estimated_duration_minutes' => 'integer',
        'qr_valid_from' => 'datetime',
        'qr_valid_until' => 'datetime',
        'review_requested' => 'boolean',
        'writer_signed_at' => 'datetime',
        'published_at' => 'datetime',
        'diffusion_ack_required' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(MeetingRoom::class, 'meeting_room_id');
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function minutesWriter()
    {
        return $this->belongsTo(User::class, 'minutes_writer_id');
    }

    public function administration()
    {
        return $this->belongsTo(IssuingAdministration::class, 'issuing_administration_id');
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function minutesVersions()
    {
        return $this->hasMany(MeetingMinutesVersion::class)->orderByDesc('version_no');
    }
}
