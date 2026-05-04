<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelLeaveType extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'administration_type',
        'administration_id',
        'code',
        'name',
        'description',
        'unit',
        'default_days',
        'carry_over_days',
        'requires_attachment',
        'is_paid',
        'is_active',
        'justification_zip_disk',
        'justification_zip_path',
        'justification_zip_name',
        'justification_zip_size',
        'metadata',
    ];

    protected $casts = [
        'default_days' => 'decimal:2',
        'carry_over_days' => 'decimal:2',
        'requires_attachment' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'justification_zip_size' => 'integer',
        'metadata' => 'array',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(PersonnelLeaveRequest::class, 'leave_type_id');
    }
}
