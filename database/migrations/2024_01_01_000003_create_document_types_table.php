<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();   // machine-readable key, e.g. 'company_registration'
            $table->text('description')->nullable();
            $table->enum('category', [
                'company_registration',
                'tax',
                'bank_verification',
                'business_license',
                'insurance',
                'contract',
                'nda',
                'safety_compliance',
            ]);
            $table->boolean('requires_expiry_date')->default(false);
            $table->boolean('is_mandatory_by_default')->default(true);
            // Comma-separated allowed MIME type extensions, e.g. 'pdf,jpg,jpeg,png'
            $table->string('allowed_file_types')->default('pdf,jpg,jpeg,png');
            // Maximum file size in kilobytes
            $table->unsignedInteger('max_file_size_kb')->default(10240);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
