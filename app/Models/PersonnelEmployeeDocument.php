<?php

namespace App\Models;

use App\Models\Concerns\LogsPersonnelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelEmployeeDocument extends Model
{
    use HasUuids;
    use LogsActivity;
    use LogsPersonnelActivity;

    protected $fillable = [
        'employee_id',
        'category',
        'label',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(PersonnelEmployee::class, 'employee_id');
    }
}
