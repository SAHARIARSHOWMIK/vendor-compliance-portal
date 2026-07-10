<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Notifications\VendorInvitationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * All vendor lifecycle transitions live here, not in controllers.
 * Controllers validate HTTP input and call these methods.
 *
 * Every mutating method logs an AuditLog row and returns the updated
 * Vendor model so controllers can redirect with fresh data.
 */
class VendorService
{
    // -----------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------

    public function create(array $data, User $actor): Vendor
    {
        $vendor = Vendor::create([
            'name'                => $data['name'],
            'registration_number' => $data['registration_number'] ?? null,
            'category'            => $data['category'],
            'risk_level'          => $data['risk_level'] ?? 'low',
            'contact_person'      => $data['contact_person'] ?? null,
            'email'               => $data['email'] ?? null,
            'phone'               => $data['phone'] ?? null,
            'address'             => $data['address'] ?? null,
            'country'             => $data['country'] ?? null,
            'internal_notes'      => $data['internal_notes'] ?? null,
            'assigned_reviewer_id' => $data['assigned_reviewer_id'] ?? null,
            'status'              => 'draft',
            'compliance_score'    => 0,
        ]);

        $this->audit($actor, $vendor, 'vendor_created', "Vendor '{$vendor->name}' created.", new_values: [
            'name' => $vendor->name, 'category' => $vendor->category, 'status' => 'draft',
        ]);

        return $vendor;
    }

    // -----------------------------------------------------------------
    // Update profile
    // -----------------------------------------------------------------

    public function update(Vendor $vendor, array $data, User $actor): Vendor
    {
        $old = $vendor->only(['name', 'category', 'risk_level', 'status', 'contact_person', 'email']);

        $vendor->update([
            'name'                => $data['name'],
            'registration_number' => $data['registration_number'] ?? $vendor->registration_number,
            'category'            => $data['category'],
            'risk_level'          => $data['risk_level'],
            'contact_person'      => $data['contact_person'] ?? null,
            'email'               => $data['email'] ?? null,
            'phone'               => $data['phone'] ?? null,
            'address'             => $data['address'] ?? null,
            'country'             => $data['country'] ?? null,
            'internal_notes'      => $data['internal_notes'] ?? null,
            'assigned_reviewer_id' => $data['assigned_reviewer_id'] ?? $vendor->assigned_reviewer_id,
        ]);

        $this->audit($actor, $vendor, 'vendor_updated', "Vendor '{$vendor->name}' profile updated.",
            old_values: $old,
            new_values: $vendor->only(['name', 'category', 'risk_level', 'status', 'contact_person', 'email']),
        );

        return $vendor->fresh();
    }

    // -----------------------------------------------------------------
    // Invitation flow
    // -----------------------------------------------------------------

    /**
     * Invite an external user to the vendor portal for this vendor.
     *
     * If a User account already exists for the email, we link it.
     * If not, we create a placeholder account with a random password
     * (the invitation email includes a password-reset link so they set
     * their own on first login).
     *
     * Status transition: draft/invited -> invited
     */
    public function invite(Vendor $vendor, string $email, string $contactName, User $actor): VendorUser
    {
        // Create or retrieve the user account
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $contactName,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'vendor_user',
                'status'   => 'active',
            ],
        );

        // Create or update the VendorUser link
        $token = Str::random(64);

        $vendorUser = VendorUser::updateOrCreate(
            ['user_id' => $user->id, 'vendor_id' => $vendor->id],
            [
                'invitation_status'  => 'pending',
                'invitation_token'   => $token,
                'invitation_sent_at' => now(),
            ],
        );

        // Advance vendor status
        if (in_array($vendor->status, ['draft', 'invited'], true)) {
            $vendor->update(['status' => 'invited', 'invited_at' => now()]);
        }

        // Send invitation email (uses MAIL_MAILER=log in demo)
        $user->notify(new VendorInvitationNotification($vendor, $token));

        $this->audit($actor, $vendor, 'vendor_invited',
            "Invitation sent to {$email} for vendor '{$vendor->name}'.",
            new_values: ['email' => $email, 'token_issued' => true],
        );

        return $vendorUser;
    }

    /**
     * Called when the vendor user clicks the invitation link and
     * completes their profile. Advances status to 'registered'.
     */
    public function acceptInvitation(Vendor $vendor, VendorUser $vendorUser, User $actor): Vendor
    {
        $vendorUser->update([
            'invitation_status'       => 'accepted',
            'invitation_accepted_at'  => now(),
        ]);

        $vendor->update([
            'status'         => 'registered',
            'registered_at'  => now(),
        ]);

        $this->audit($actor, $vendor, 'vendor_registered',
            "Vendor '{$vendor->name}' completed registration.",
        );

        return $vendor->fresh();
    }

    // -----------------------------------------------------------------
    // Status transitions (admin-initiated)
    // -----------------------------------------------------------------

    /**
     * Move vendor to 'documents_pending' after registration — this is
     * triggered by ComplianceService after the checklist is generated.
     * Called here as well for admin-initiated "request documents" action.
     */
    public function requestDocuments(Vendor $vendor, User $actor): Vendor
    {
        $vendor->update(['status' => 'documents_pending']);

        $this->audit($actor, $vendor, 'documents_requested',
            "Vendor '{$vendor->name}' set to Documents Pending.",
        );

        return $vendor->fresh();
    }

    public function suspend(Vendor $vendor, string $reason, User $actor): Vendor
    {
        $old = $vendor->status;
        $vendor->update(['status' => 'suspended', 'compliance_status' => 'suspended']);

        $this->audit($actor, $vendor, 'vendor_suspended',
            "Vendor '{$vendor->name}' suspended. Reason: {$reason}",
            old_values: ['status' => $old],
            new_values: ['status' => 'suspended', 'reason' => $reason],
        );

        return $vendor->fresh();
    }

    public function archive(Vendor $vendor, User $actor): Vendor
    {
        $old = $vendor->status;
        $vendor->update(['status' => 'archived']);

        $this->audit($actor, $vendor, 'vendor_archived',
            "Vendor '{$vendor->name}' archived.",
            old_values: ['status' => $old],
            new_values: ['status' => 'archived'],
        );

        return $vendor->fresh();
    }

    public function reinstate(Vendor $vendor, User $actor): Vendor
    {
        $old = $vendor->status;
        // Return to the most recent meaningful status based on what
        // documents they have — ComplianceService will recalculate.
        $vendor->update(['status' => 'registered', 'compliance_status' => null]);

        $this->audit($actor, $vendor, 'vendor_reinstated',
            "Vendor '{$vendor->name}' reinstated from {$old}.",
            old_values: ['status' => $old],
            new_values: ['status' => 'registered'],
        );

        return $vendor->fresh();
    }

    public function assignReviewer(Vendor $vendor, ?int $reviewerId, User $actor): Vendor
    {
        $old = $vendor->assigned_reviewer_id;
        $vendor->update(['assigned_reviewer_id' => $reviewerId]);

        $reviewerName = $reviewerId
            ? User::find($reviewerId)?->name ?? "user #{$reviewerId}"
            : 'unassigned';

        $this->audit($actor, $vendor, 'reviewer_assigned',
            "Reviewer for '{$vendor->name}' set to: {$reviewerName}.",
            old_values: ['assigned_reviewer_id' => $old],
            new_values: ['assigned_reviewer_id' => $reviewerId],
        );

        return $vendor->fresh();
    }

    // -----------------------------------------------------------------
    // Audit helper
    // -----------------------------------------------------------------

    private function audit(
        User $actor,
        Vendor $vendor,
        string $eventType,
        string $description,
        ?array $old_values = null,
        ?array $new_values = null,
    ): void {
        AuditLog::create([
            'actor_id'    => $actor->id,
            'actor_name'  => $actor->name,
            'actor_role'  => $actor->role->value,
            'vendor_id'   => $vendor->id,
            'vendor_name' => $vendor->name,
            'event_type'  => $eventType,
            'description' => $description,
            'old_values'  => $old_values,
            'new_values'  => $new_values,
            'occurred_at' => now(),
        ]);
    }
}
