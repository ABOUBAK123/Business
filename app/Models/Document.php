<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'file_path', 'signed_file_path', 'file_size', 'mime_type',
        'status', 'owner_id', 'created_by', 'issuing_administration_id',
        'recipient_administration_id', 'document_number', 'sub_entity_code', 'qr_token', 'signed_at',
    ];

    protected $casts = ['signed_at' => 'datetime', 'file_size' => 'integer'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function versions() { return $this->hasMany(DocumentVersion::class); }
    public function signatures() { return $this->hasMany(Signature::class); }
    public function qrCodes() { return $this->hasMany(QrCode::class); }
    public function executions() { return $this->hasMany(WorkflowExecution::class); }
    public function preferences() { return $this->hasMany(DocumentUserPreference::class); }
    public function signatureRequests() { return $this->hasMany(SignatureRequest::class); }
    public function issuingAdministration() { return $this->belongsTo(IssuingAdministration::class, 'issuing_administration_id'); }
}
