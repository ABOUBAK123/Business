<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClamAvScanLog extends Model
{
    protected $table     = 'clamav_scan_logs';
    public $incrementing = false;
    protected $keyType   = 'string';
    public $timestamps   = false;

    protected $fillable = [
        'id',
        'file_name',
        'file_size',
        'mime_type',
        'result',
        'threat',
        'context',
        'scanned_by',
        'ip_address',
        'scanner_output',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'file_size'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
