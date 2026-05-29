<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class QrCode extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'article_id', 'branch_id', 'code', 'image_path', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
