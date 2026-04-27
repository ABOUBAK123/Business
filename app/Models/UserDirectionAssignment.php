<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserDirectionAssignment extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'direction_scope_type', 'direction_scope_id', 'sub_entity_code', 'direction_label'];

    public function user() { return $this->belongsTo(User::class); }
}
