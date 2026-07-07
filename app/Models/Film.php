<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Film extends Model
{
    protected $fillable = [
        'title', 'original_title', 'description', 'poster_path',
        'trailer_url', 'hls_manifest_url', 'genre', 'duration',
        'release_year', 'price', 'currency', 'drm_key', 'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'price'        => 'decimal:2',
        'duration'     => 'integer',
        'release_year' => 'integer',
    ];

    public function getPosterUrlAttribute(): ?string
    {
        if (!$this->poster_path) return null;
        return Storage::url($this->poster_path);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return '—';
        $h = intdiv($this->duration, 60);
        $m = $this->duration % 60;
        return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
