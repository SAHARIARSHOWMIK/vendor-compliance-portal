<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('message');

            $table->enum('type', [
                'info',
                'success',
                'warning',
                'urgent',
                'action_required',
            ])->default('info');

            // Related records (nullable — a notification may relate to
            // just a vendor, just a document, or both)
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();
            $table->foreignId('vendor_document_id')
                ->nullable()
                ->constrained('vendor_documents')
                ->nullOnDelete();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Optional action URL so the notification can link directly
            // to the relevant page (e.g. the document review page)
            $table->string('action_url')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
