<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdministrationProfile extends Model
{
    use HasUuids;

    protected $fillable = ['administration_id', 'name', 'description', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function administration() { return $this->belongsTo(IssuingAdministration::class, 'administration_id'); }
    public function users() { return $this->hasMany(AdministrationUser::class, 'profile_id'); }
}
