<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_document_id',
        'vendor_id',
        'document_type_id',
        'uploaded_by',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size_kb',
        'version_number',
        'status_at_snapshot',
        'expiry_date',
        'change_note',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'uploaded_at' => 'datetime',
        ];
    }

    public function vendorDocument(): BelongsTo
    {
        return $this->belongsTo(VendorDocument::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
