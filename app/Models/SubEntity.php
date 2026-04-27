<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SubEntity extends Model
{
    use HasUuids;

    protected $fillable = [
        'scope_type', 'scope_id', 'name', 'code', 'parent_code',
        'direction_type_id', 'manager_name', 'manager_email', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function directionType()
    {
        return $this->belongsTo(DirectionType::class, 'direction_type_id');
    }
}
