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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('extension', 20);
            $table->foreignId('current_version_id')
                ->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('folder_id')->constrained('folders')->restrictOnDelete()->cascadeOnUpdate();

            $table->softDeletes();

            $table->index('folder_id');
            $table->index('extension');
            $table->index('created_at');
            $table->index('title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
