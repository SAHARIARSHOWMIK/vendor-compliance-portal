<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // Core profile
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->enum('category', [
                'general_supplier',
                'it_vendor',
                'contractor',
                'consultant',
                'high_risk',
            ]);
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('country', 2)->nullable();   // ISO 3166-1 alpha-2

            // Vendor lifecycle (12 statuses from spec section 6)
            $table->enum('status', [
                'draft',
                'invited',
                'registered',
                'documents_pending',
                'under_review',
                'correction_required',
                'partially_approved',
                'fully_compliant',
                'expiring_soon',
                'non_compliant',
                'suspended',
                'archived',
            ])->default('draft');

            // Compliance summary (denormalised for dashboard queries —
            // the authoritative calculation is in ComplianceCheck; these
            // columns are updated by ComplianceService::recalculate()).
            $table->enum('compliance_status', [
                'fully_compliant',
                'partially_compliant',
                'documents_missing',
                'under_review',
                'correction_required',
                'expiring_soon',
                'non_compliant',
                'suspended',
            ])->nullable();
            $table->unsignedTinyInteger('compliance_score')->default(0); // 0-100

            // Assignment
            $table->foreignId('assigned_reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Internal notes (not visible to vendor users)
            $table->text('internal_notes')->nullable();

            // Invitation tracking
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('compliance_status');
            $table->index('risk_level');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
