<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AdministrationSmtpSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'administration_id',
        'administration_type',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    protected $casts = [
        'mail_port' => 'integer',
    ];

    /** Encrypt password on set, decrypt on get. */
    public function setMailPasswordAttribute(?string $value): void
    {
        $this->attributes['mail_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getMailPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // already plain (legacy)
        }
    }

    public static function forAdministration(string $id, string $type): ?self
    {
        return static::where('administration_id', $id)
                     ->where('administration_type', $type)
                     ->first();
    }
}
