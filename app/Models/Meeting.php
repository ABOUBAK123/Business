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
        'estimated_duration_minutes',
        'organizer_id',
        'minutes_writer_id',
        'agenda',
        'attachments',
        'priority',
        'confidentiality',
        'status',
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
        'recurrence_until' => 'date',
        'attachments' => 'array',
        'recurrence_exceptions' => 'array',
        'estimated_duration_minutes' => 'integer',
        'qr_valid_from' => 'datetime',
        'qr_valid_until' => 'datetime',
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

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }
}
