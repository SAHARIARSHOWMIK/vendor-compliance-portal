<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleName::class,
            'last_login_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * If this user is a VendorUser, the pivot row linking them to their
     * vendor company. Null for internal-role users.
     */
    public function vendorUser(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    // -----------------------------------------------------------------
    // Role helpers
    // -----------------------------------------------------------------

    public function hasRole(RoleName $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin);
    }

    public function isComplianceAdmin(): bool
    {
        return $this->hasRole(RoleName::ComplianceAdmin);
    }

    public function isReviewer(): bool
    {
        return $this->hasRole(RoleName::Reviewer);
    }

    public function isVendorUser(): bool
    {
        return $this->hasRole(RoleName::VendorUser);
    }

    public function isAuditor(): bool
    {
        return $this->hasRole(RoleName::Auditor);
    }

    /** Internal staff = anyone who isn't a VendorUser. */
    public function isInternal(): bool
    {
        return $this->hasAnyRole(RoleName::internalRoles());
    }

    /**
     * The vendor company this user belongs to, if any (VendorUser role
     * only). Internal-role users return null - they aren't scoped to a
     * single vendor.
     */
    public function vendor(): ?Vendor
    {
        return $this->vendorUser()->with('vendor')->first()?->vendor;
    }

    /** True for any role permitted to read-only auditors as well. */
    public function isReadOnly(): bool
    {
        return $this->isAuditor();
    }
}
