<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recipient_administrations')) {
            return;
        }

        if (!Schema::hasColumn('recipient_administrations', 'code')) {
            Schema::table('recipient_administrations', function (Blueprint $table) {
                $table->string('code', 100)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('recipient_administrations')) {
            return;
        }

        if (Schema::hasColumn('recipient_administrations', 'code')) {
            Schema::table('recipient_administrations', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
