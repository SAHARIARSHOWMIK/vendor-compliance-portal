<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceCheck extends Model
{
    protected $fillable = [
        'vendor_id',
        'total_required',
        'total_uploaded',
        'total_approved',
        'total_missing',
        'total_rejected',
        'total_expired',
        'total_expiring_soon',
        'total_pending_review',
        'compliance_score',
        'overall_status',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isFullyCompliant(): bool
    {
        return $this->overall_status === 'fully_compliant';
    }
}
