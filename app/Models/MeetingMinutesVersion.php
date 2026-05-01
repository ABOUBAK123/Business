<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MeetingMinutesVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'meeting_id',
        'version_no',
        'content',
        'created_by',
        'note',
        'workflow_status',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
