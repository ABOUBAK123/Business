<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelEmployee extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'user_id',
        'sub_entity_id',
        'administration_type',
        'administration_id',
        'employee_number',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'birth_place',
        'marital_status',
        'phone',
        'secondary_phone',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'job_title',
        'hire_date',
        'employment_status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = ['full_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subEntity(): BelongsTo
    {
        return $this->belongsTo(SubEntity::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PersonnelEmployeeDocument::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(PersonnelLeaveRequest::class, 'employee_id');
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(PersonnelTrainingEnrollment::class, 'employee_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(PersonnelEmployeeSkill::class, 'employee_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(PersonnelGoal::class, 'employee_id');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PersonnelPerformanceReview::class, 'employee_id');
    }

    public function careerEvents(): HasMany
    {
        return $this->hasMany(PersonnelCareerEvent::class, 'employee_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
