<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number',
        'category',
        'risk_level',
        'contact_person',
        'email',
        'phone',
        'address',
        'country',
        'status',
        'compliance_status',
        'compliance_score',
        'assigned_reviewer_id',
        'internal_notes',
        'invited_at',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'registered_at' => 'datetime',
            'compliance_score' => 'integer',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    public function vendorUsers(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, VendorUser::class, 'vendor_id', 'id', 'id', 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    public function latestComplianceCheck(): HasOne
    {
        return $this->hasOne(ComplianceCheck::class)->latestOfMany('checked_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -----------------------------------------------------------------
    // Lifecycle helpers
    // -----------------------------------------------------------------

    public function isFullyCompliant(): bool
    {
        return $this->compliance_status === 'fully_compliant';
    }

    public function isExpiringSoon(): bool
    {
        return in_array($this->status, ['expiring_soon'], true)
            || $this->compliance_status === 'expiring_soon';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function canUploadDocuments(): bool
    {
        return ! $this->isSuspended() && ! $this->isArchived();
    }

    /**
     * Documents that still need to be uploaded for this vendor's
     * category/risk combination. Used by the compliance engine and
     * the vendor portal checklist.
     */
    public function requiredDocumentTypes()
    {
        return VendorCategoryRequirement::where('vendor_category', $this->category)
            ->where('requirement_level', 'required')
            ->where(function ($q) {
                $q->whereNull('min_risk_level')
                    ->orWhere('min_risk_level', '<=', $this->risk_level);
            })
            ->with('documentType')
            ->get()
            ->pluck('documentType');
    }
}
