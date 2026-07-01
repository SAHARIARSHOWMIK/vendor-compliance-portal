<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the result of ComplianceService::recalculate() for each
     * vendor. A new row is appended on every recalculation; the latest
     * row is the authoritative current compliance state. The history of
     * rows shows how compliance has changed over time (useful for audit).
     *
     * Denormalised counts here drive the admin dashboard metrics without
     * requiring an expensive COUNT()/JOIN on every page load.
     */
    public function up(): void
    {
        Schema::create('compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

            // Document counts at time of calculation
            $table->unsignedSmallInteger('total_required')->default(0);
            $table->unsignedSmallInteger('total_uploaded')->default(0);
            $table->unsignedSmallInteger('total_approved')->default(0);
            $table->unsignedSmallInteger('total_missing')->default(0);
            $table->unsignedSmallInteger('total_rejected')->default(0);
            $table->unsignedSmallInteger('total_expired')->default(0);
            $table->unsignedSmallInteger('total_expiring_soon')->default(0);
            $table->unsignedSmallInteger('total_pending_review')->default(0);

            // 0-100 score computed by ComplianceService
            $table->unsignedTinyInteger('compliance_score')->default(0);

            // One of the 8 compliance statuses from spec section 15
            $table->enum('overall_status', [
                'fully_compliant',
                'partially_compliant',
                'documents_missing',
                'under_review',
                'correction_required',
                'expiring_soon',
                'non_compliant',
                'suspended',
            ]);

            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            $table->index(['vendor_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_checks');
    }
};
