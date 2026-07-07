<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('films', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->text('description')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('trailer_url')->nullable();
            $table->text('hls_manifest_url')->nullable();
            $table->string('genre')->nullable();
            $table->unsignedSmallInteger('duration')->nullable()->comment('durée en minutes');
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('XOF');
            $table->string('drm_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('films');
    }
};
