<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value', 'group', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);
        return $row ? $row->value : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general', bool $isSecret = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'is_secret' => $isSecret]
        );
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->keyBy('key')
            ->map(fn ($s) => $s->value)
            ->toArray();
    }

    public static function bulkSet(array $data, string $group, array $secrets = []): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value, $group, in_array($key, $secrets));
        }
    }
}
