<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    private const ENCRYPTED_PREFIX = 'enc:';

    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value', 'group', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);
        if (! $row) {
            return $default;
        }

        $value = static::decodeStoredValue($row->value, (bool) $row->is_secret);

        if ($row->is_secret && is_string($row->value) && $row->value !== '' && ! str_starts_with($row->value, self::ENCRYPTED_PREFIX)) {
            $row->forceFill([
                'value' => self::encodeStoredValue($value, true),
            ])->save();
        }

        return $value;
    }

    public static function set(string $key, mixed $value, string $group = 'general', bool $isSecret = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::encodeStoredValue($value, $isSecret),
                'group' => $group,
                'is_secret' => $isSecret,
            ]
        );
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->keyBy('key')
            ->map(function ($s) {
                $decoded = self::decodeStoredValue($s->value, (bool) $s->is_secret);

                if ($s->is_secret && is_string($s->value) && $s->value !== '' && ! str_starts_with($s->value, self::ENCRYPTED_PREFIX)) {
                    $s->forceFill([
                        'value' => self::encodeStoredValue($decoded, true),
                    ])->save();
                }

                return $decoded;
            })
            ->toArray();
    }

    public static function bulkSet(array $data, string $group, array $secrets = []): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value, $group, in_array($key, $secrets));
        }
    }

    private static function encodeStoredValue(mixed $value, bool $isSecret): mixed
    {
        if (! $isSecret || $value === null || $value === '') {
            return $value;
        }

        if (! is_string($value)) {
            $value = (string) $value;
        }

        if (str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        return self::ENCRYPTED_PREFIX . Crypt::encryptString($value);
    }

    private static function decodeStoredValue(mixed $value, bool $isSecret): mixed
    {
        if (! $isSecret || ! is_string($value) || $value === '') {
            return $value;
        }

        if (! str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        $ciphertext = substr($value, strlen(self::ENCRYPTED_PREFIX));

        try {
            return Crypt::decryptString($ciphertext);
        } catch (\Throwable) {
            return null;
        }
    }
}
