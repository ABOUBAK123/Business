<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonnelJobReference extends Model
{
    use HasUuids;

    protected $fillable = [
        'administration_type',
        'administration_id',
        'reference_type',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
