<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This table drives the dynamic required-document checklist:
     * given a vendor's category (and optionally their risk level),
     * which document types must they upload?
     *
     * The seeder (Database\Seeders\DocumentTypeSeeder) populates
     * this with the 5-category × document-type matrix from the spec.
     */
    public function up(): void
    {
        Schema::create('vendor_category_requirements', function (Blueprint $table) {
            $table->id();
            $table->enum('vendor_category', [
                'general_supplier',
                'it_vendor',
                'contractor',
                'consultant',
                'high_risk',
            ]);
            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->cascadeOnDelete();
            $table->enum('requirement_level', ['required', 'optional'])->default('required');
            // If set, this requirement only applies when vendor risk level
            // is at or above this threshold (e.g. insurance required for
            // high-risk contractors but optional for medium).
            $table->enum('min_risk_level', ['low', 'medium', 'high'])->nullable();
            $table->boolean('expiry_required')->default(false);
            $table->timestamps();

            // A category + document type pair is unique per risk condition
            $table->unique(['vendor_category', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_category_requirements');
    }
};
