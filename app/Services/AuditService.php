<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\Request;

/**
 * Centralised audit logging service.
 *
 * Every significant action in the system calls AuditService::log()
 * rather than creating AuditLog rows directly. This ensures:
 *   - IP address and user agent are captured consistently
 *   - Actor snapshots (name/role) are always recorded
 *   - Vendor snapshots are always recorded
 *   - The occurred_at timestamp is always set
 *
 * Event type conventions (snake_case, past tense):
 *   vendor_created, vendor_updated, vendor_invited, vendor_registered,
 *   vendor_suspended, vendor_reinstated, vendor_archived,
 *   document_uploaded, document_reuploaded,
 *   document_approved, document_rejected, correction_requested,
 *   more_info_requested, document_escalated,
 *   compliance_recalculated, expiry_warning_sent,
 *   report_exported, notification_sent,
 *   reviewer_assigned, documents_requested
 */
class AuditService
{
    public function log(
        string      $eventType,
        string      $description,
        ?User       $actor        = null,
        ?Vendor     $vendor       = null,
        ?VendorDocument $document = null,
        ?array      $oldValues    = null,
        ?array      $newValues    = null,
        ?Request    $request      = null,
    ): AuditLog {
        $req = $request ?? (app()->bound('request') ? request() : null);

        return AuditLog::create([
            'actor_id'           => $actor?->id,
            'actor_name'         => $actor?->name,
            'actor_role'         => $actor?->role?->value,
            'vendor_id'          => $vendor?->id,
            'vendor_name'        => $vendor?->name,
            'vendor_document_id' => $document?->id,
            'event_type'         => $eventType,
            'description'        => $description,
            'old_values'         => $oldValues,
            'new_values'         => $newValues,
            'ip_address'         => $req?->ip(),
            'user_agent'         => $req?->userAgent(),
            'occurred_at'        => now(),
        ]);
    }

    /**
     * Log a system-initiated event (no human actor — e.g. scheduled
     * expiry check, automated compliance recalculation).
     */
    public function logSystem(
        string          $eventType,
        string          $description,
        ?Vendor         $vendor   = null,
        ?VendorDocument $document = null,
        ?array          $newValues = null,
    ): AuditLog {
        return $this->log(
            eventType:   $eventType,
            description: $description,
            actor:       null,
            vendor:      $vendor,
            document:    $document,
            newValues:   $newValues,
        );
    }
}
