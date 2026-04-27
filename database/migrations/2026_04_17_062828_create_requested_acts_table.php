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
        Schema::create('requested_acts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('administration_id');       // UUID IssuingAdministration
            $table->string('direction_code')->nullable(); // code de la SubEntity
            $table->string('document_name');
            $table->json('required_documents')->nullable(); // ["doc1","doc2",...]
            $table->json('applicant_fields')->nullable();   // [{"label":"...","inputType":"text"},...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_acts');
    }
};
