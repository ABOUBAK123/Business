<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelEmployeeSkill extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'employee_id',
        'administration_type',
        'administration_id',
        'skill_name',
        'category',
        'current_level',
        'target_level',
        'assessment_date',
        'source',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'current_level' => 'integer',
        'target_level' => 'integer',
        'assessment_date' => 'date',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(PersonnelEmployee::class, 'employee_id');
    }
}
