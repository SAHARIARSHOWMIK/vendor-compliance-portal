<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorDocument extends Model
{
    protected $fillable = [
        'vendor_id',
        'document_type_id',
        'uploaded_by',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size_kb',
        'version_number',
        'status',
        'expiry_date',
        'uploaded_at',
        'reviewed_at',
        'reviewed_by',
        'review_comment',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->orderBy('reviewed_at', 'desc');
    }

    public function latestReview(): ?Review
    {
        return $this->reviews()->first();
    }

    // -----------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCorrectionRequested(): bool
    {
        return $this->status === 'correction_requested';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expiry_date && $this->expiry_date->isPast());
    }

    public function isExpiringSoon(int $withinDays = 60): bool
    {
        if (! $this->expiry_date || $this->isExpired()) {
            return false;
        }

        return $this->expiry_date->diffInDays(now(), false) <= 0
            && $this->expiry_date->diffInDays(now()) <= $withinDays;
    }

    /**
     * Days until expiry — negative means already expired.
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * The document counts as "valid" for compliance purposes only when
     * it is approved AND not expired.
     */
    public function isCompliant(): bool
    {
        return $this->isApproved() && ! $this->isExpired();
    }
}
