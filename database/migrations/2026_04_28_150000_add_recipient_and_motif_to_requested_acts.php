<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requested_acts', function (Blueprint $table) {
            $table->uuid('recipient_administration_id')->nullable()->after('direction_code');
            $table->text('motif')->nullable()->after('recipient_administration_id');
        });
    }

    public function down(): void
    {
        Schema::table('requested_acts', function (Blueprint $table) {
            $table->dropColumn(['recipient_administration_id', 'motif']);
        });
    }
};
