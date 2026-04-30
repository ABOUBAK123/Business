<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->uuid('administration_id')->nullable()->after('id')->index();
            $table->foreign('administration_id')
                ->references('id')
                ->on('issuing_administrations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->dropForeign(['administration_id']);
            $table->dropColumn('administration_id');
        });
    }
};
