<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelTrainingEnrollment extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'employee_id',
        'training_id',
        'assigned_by_user_id',
        'administration_type',
        'administration_id',
        'status',
        'planned_start_date',
        'planned_end_date',
        'started_at',
        'completed_at',
        'attendance_rate',
        'score',
        'satisfaction_score',
        'certificate_disk',
        'certificate_path',
        'certificate_original_name',
        'certificate_mime_type',
        'certificate_size',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'attendance_rate' => 'decimal:2',
        'score' => 'decimal:2',
        'satisfaction_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(PersonnelEmployee::class, 'employee_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(PersonnelTraining::class, 'training_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
