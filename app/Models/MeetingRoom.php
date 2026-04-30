<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MeetingRoom extends Model
{
    use HasUuids;

    protected $fillable = [
        'administration_id',
        'name',
        'capacity',
        'location',
        'equipments',
        'description',
        'photo_path',
        'status',
        'maintenance_status',
    ];

    protected $casts = [
        'equipments' => 'array',
        'capacity' => 'integer',
    ];

    public function administration()
    {
        return $this->belongsTo(\App\Models\IssuingAdministration::class, 'administration_id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'meeting_room_id');
    }
}
