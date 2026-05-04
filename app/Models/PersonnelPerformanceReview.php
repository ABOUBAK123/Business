<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelPerformanceReview extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'employee_id',
        'reviewer_user_id',
        'administration_type',
        'administration_id',
        'review_type',
        'title',
        'period_label',
        'scheduled_at',
        'completed_at',
        'status',
        'overall_score',
        'strengths',
        'improvements',
        'manager_comments',
        'employee_comments',
        'recommendations',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
        'completed_at' => 'datetime',
        'overall_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(PersonnelEmployee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
