<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "current version" table for vendor documents.
     *
     * Key design decisions:
     *   - One row per vendor × document_type. Previous uploads are moved
     *     to document_versions rather than overwriting this row, so the
     *     current row always reflects the latest submission.
     *   - file_path is relative to the 'vendor_documents' disk defined in
     *     config/filesystems.php — never a public URL.
     *   - status drives the document lifecycle (10 statuses from spec §7).
     *   - review_* columns are denormalised from the latest Review row for
     *     fast dashboard queries; the authoritative history is in reviews.
     */
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->foreignId('uploaded_by')->constrained('users');

            // File metadata
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_kb');
            $table->unsignedSmallInteger('version_number')->default(1);

            // Document lifecycle (10 statuses from spec section 7)
            $table->enum('status', [
                'required',
                'uploaded',
                'under_review',
                'approved',
                'rejected',
                'correction_requested',
                'reuploaded',
                'expiring_soon',
                'expired',
                'archived',
            ])->default('uploaded');

            // Dates
            $table->date('expiry_date')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();

            // Denormalised review result (authoritative history in reviews)
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('review_comment')->nullable();

            // Vendor-supplied notes / version description
            $table->text('notes')->nullable();

            $table->timestamps();

            // One current document per vendor per type
            $table->unique(['vendor_id', 'document_type_id']);
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
