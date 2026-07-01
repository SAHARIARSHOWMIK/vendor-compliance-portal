<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'vendor_document_id',
        'vendor_id',
        'reviewer_id',
        'decision',
        'comment',
        'document_version',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isApproval(): bool
    {
        return $this->decision === 'approved';
    }

    public function isRejection(): bool
    {
        return $this->decision === 'rejected';
    }

    public function isCorrectionRequest(): bool
    {
        return $this->decision === 'correction_requested';
    }
}
