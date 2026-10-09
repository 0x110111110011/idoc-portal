<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('version_number');
            $table->string('original_name');
            $table->text('stored_path');
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);

            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete()->cascadeOnUpdate();

            $table->unique([
                'document_id',
                'version_number',
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
