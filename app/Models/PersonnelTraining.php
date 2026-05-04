<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelTraining extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'administration_type',
        'administration_id',
        'code',
        'title',
        'category',
        'provider_name',
        'delivery_mode',
        'duration_hours',
        'budget_amount',
        'validity_months',
        'is_mandatory',
        'is_active',
        'description',
        'objectives',
        'skills',
        'metadata',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'validity_months' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'skills' => 'array',
        'metadata' => 'array',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(PersonnelTrainingEnrollment::class, 'training_id');
    }
}
