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
        Schema::create('sub_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scope_type');                    // 'emitter' | 'recipient'
            $table->string('scope_id');                      // UUID de l'administration parente
            $table->string('name');
            $table->string('code')->unique();
            $table->string('parent_code')->nullable();
            $table->string('direction_type_id')->nullable(); // UUID du DirectionType
            $table->string('manager_name')->nullable();
            $table->string('manager_email')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_entities');
    }
};
