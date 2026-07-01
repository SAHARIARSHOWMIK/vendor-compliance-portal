<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail. Every significant action in the system
     * writes a row here via AuditService::log(). Rows are never updated
     * or deleted — this table is the immutable compliance evidence log
     * the spec requires (section 10 / page 10 Audit Log page).
     *
     * event_type uses string rather than enum so new event types can be
     * added without a schema migration (enum columns require rebuilding
     * the table in MySQL on modification).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who did it
            $table->foreignId('actor_id')
                ->nullable()                // null = system (e.g. scheduled expiry check)
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_name')->nullable();    // snapshot in case user is deleted
            $table->string('actor_role', 50)->nullable();

            // What it affected
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();
            $table->string('vendor_name')->nullable();   // snapshot

            $table->foreignId('vendor_document_id')
                ->nullable()
                ->constrained('vendor_documents')
                ->nullOnDelete();

            // Event
            $table->string('event_type', 100);       // e.g. 'document_uploaded'
            $table->text('description');

            // Before/after for change events
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Optional context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index('event_type');
            $table->index('occurred_at');
            $table->index(['vendor_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
