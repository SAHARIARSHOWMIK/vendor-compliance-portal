<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only table — review decisions are never updated or deleted.
     * The latest row for a given vendor_document_id is the current
     * decision; earlier rows are the review history visible on the
     * document detail page.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_document_id')
                ->constrained('vendor_documents')
                ->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');

            // Five decision types from spec section 6 / Stage 6
            $table->enum('decision', [
                'approved',
                'rejected',
                'correction_requested',
                'need_more_info',
                'escalated',
            ]);

            $table->text('comment')->nullable();

            // Snapshot the document version number at decision time so
            // the history page can show "reviewer approved v2" accurately
            // even after v3 is uploaded.
            $table->unsignedSmallInteger('document_version')->nullable();

            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['vendor_document_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
