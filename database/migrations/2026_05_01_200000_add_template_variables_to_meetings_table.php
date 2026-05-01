<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Stores detected variables from uploaded DOCX template (JSON array of strings)
            $table->json('template_variables')->nullable()->after('minutes_template');
            // Stores the path of the "sealed" template (after @@@ zones are processed)
            $table->string('template_sealed_path', 512)->nullable()->after('template_variables');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['template_variables', 'template_sealed_path']);
        });
    }
};
