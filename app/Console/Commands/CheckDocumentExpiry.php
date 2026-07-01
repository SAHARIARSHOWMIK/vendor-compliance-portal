<?php

namespace App\Console\Commands;

use App\Models\VendorDocument;
use App\Services\AuditService;
use App\Services\ComplianceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Nightly command that scans all approved, non-archived vendor documents
 * for expiry dates within the three alert windows defined in .env:
 *   COMPLIANCE_EXPIRY_EARLY_WARNING_DAYS  (default 60)
 *   COMPLIANCE_EXPIRY_REMINDER_DAYS       (default 30)
 *   COMPLIANCE_EXPIRY_URGENT_DAYS         (default 7)
 *
 * For each document found:
 *   1. Updates vendor_documents.status to 'expiring_soon' or 'expired'
 *   2. Creates in-app + email notifications
 *   3. Triggers ComplianceService::recalculate() so vendor status reflects
 *      the new expiry state
 *   4. Appends an audit log row
 *
 * Run manually:  php artisan compliance:check-expiry
 * Scheduled:     daily at 08:00 (see routes/console.php)
 */
class CheckDocumentExpiry extends Command
{
    protected $signature   = 'compliance:check-expiry {--dry-run : Show what would happen without making changes}';
    protected $description = 'Check all vendor documents for upcoming or past expiry dates and update compliance status.';

    public function __construct(
        private readonly ComplianceService  $complianceService,
        private readonly NotificationService $notificationService,
        private readonly AuditService        $auditService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun        = $this->option('dry-run');
        $earlyWarning  = (int) env('COMPLIANCE_EXPIRY_EARLY_WARNING_DAYS', 60);
        $urgentDays    = (int) env('COMPLIANCE_EXPIRY_URGENT_DAYS', 7);

        $this->info("Checking document expiry (dry-run: " . ($dryRun ? 'YES' : 'NO') . ")");

        // --- Expired documents ---
        $expired = VendorDocument::with(['vendor', 'documentType'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->whereNotIn('status', ['expired', 'archived'])
            ->get();

        $this->info("Found {$expired->count()} newly-expired document(s).");

        foreach ($expired as $doc) {
            $this->line("  EXPIRED: [{$doc->vendor->name}] {$doc->documentType->name} (expired {$doc->expiry_date->format('d M Y')})");

            if (! $dryRun) {
                $doc->update(['status' => 'expired']);

                $this->notificationService->notifyExpiryWarning($doc, $doc->daysUntilExpiry() ?? 0);

                $this->auditService->logSystem(
                    'document_expired',
                    "Document '{$doc->documentType->name}' for {$doc->vendor->name} has expired.",
                    vendor:    $doc->vendor,
                    document:  $doc,
                    newValues: ['status' => 'expired', 'expiry_date' => $doc->expiry_date->toDateString()],
                );

                $this->complianceService->recalculate($doc->vendor);
            }
        }

        // --- Documents expiring within the early-warning window ---
        $expiringSoon = VendorDocument::with(['vendor', 'documentType'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now()->toDateString())
            ->where('expiry_date', '<=', now()->addDays($earlyWarning)->toDateString())
            ->whereIn('status', ['approved', 'expiring_soon'])
            ->get();

        $this->info("Found {$expiringSoon->count()} document(s) expiring within {$earlyWarning} days.");

        $vendorsToRecalc = collect();

        foreach ($expiringSoon as $doc) {
            $daysLeft = $doc->daysUntilExpiry();
            $this->line("  EXPIRING: [{$doc->vendor->name}] {$doc->documentType->name} — {$daysLeft} day(s) remaining");

            if (! $dryRun) {
                if ($doc->status !== 'expiring_soon') {
                    $doc->update(['status' => 'expiring_soon']);
                }

                // Only send notifications at the defined thresholds to
                // avoid spamming vendors with daily emails.
                $shouldNotify = in_array($daysLeft, [
                    $earlyWarning,
                    (int) env('COMPLIANCE_EXPIRY_REMINDER_DAYS', 30),
                    $urgentDays,
                    1,   // final day reminder
                ], true);

                if ($shouldNotify) {
                    $this->notificationService->notifyExpiryWarning($doc, $daysLeft);

                    $this->auditService->logSystem(
                        'expiry_warning_sent',
                        "Expiry warning sent for '{$doc->documentType->name}' ({$daysLeft} days remaining).",
                        vendor:    $doc->vendor,
                        document:  $doc,
                        newValues: ['days_until_expiry' => $daysLeft],
                    );
                }

                $vendorsToRecalc->push($doc->vendor_id);
            }
        }

        // Recalculate compliance for every affected vendor (deduplicated)
        if (! $dryRun) {
            $vendorsToRecalc->unique()->each(function ($vendorId) {
                $vendor = \App\Models\Vendor::find($vendorId);
                if ($vendor) {
                    $this->complianceService->recalculate($vendor);
                }
            });
        }

        $total = $expired->count() + $expiringSoon->count();
        $this->info("Done. Processed {$total} document(s).");

        return self::SUCCESS;
    }
}
