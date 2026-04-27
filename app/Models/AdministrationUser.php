<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdministrationUser extends Model
{
    use HasUuids;

    protected $fillable = ['administration_id', 'profile_id', 'full_name', 'email', 'username', 'password_hash', 'admin_role', 'status'];
    protected $hidden = ['password_hash'];

    public function administration() { return $this->belongsTo(IssuingAdministration::class, 'administration_id'); }
    public function profile() { return $this->belongsTo(AdministrationProfile::class, 'profile_id'); }
}
