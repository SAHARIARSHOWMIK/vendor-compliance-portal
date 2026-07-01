<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable upload history. When a vendor reuploads a document:
     *   1. The current vendor_documents row's file_path/status is snapshotted
     *      here as a new document_versions row.
     *   2. vendor_documents is updated with the new file and status='reuploaded'.
     *
     * This ensures the spec requirement "old version remains in history"
     * and enables the version-history timeline on the document review page.
     */
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_document_id')
                ->constrained('vendor_documents')
                ->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->foreignId('uploaded_by')->constrained('users');

            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_kb');
            $table->unsignedSmallInteger('version_number');

            // Status this version had at the time it was superseded
            $table->string('status_at_snapshot', 50)->nullable();

            $table->date('expiry_date')->nullable();
            $table->text('change_note')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['vendor_document_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
