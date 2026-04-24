<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void  
    {
        Schema::create('media_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->enum('type', ['audio', 'caption', 'animation']);
            $table->enum('status', ['queued','processing','completed','failed'])->default('queued');
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->json('metadata')->nullable();   // preset, model, options used
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_jobs');
    }
};
