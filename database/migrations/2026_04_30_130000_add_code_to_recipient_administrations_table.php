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

        if (!Schema::hasColumn('recipient_administrations', 'logo')) {
            Schema::table('recipient_administrations', function (Blueprint $table) {
                $table->string('logo', 500)->nullable()->after('email_address');
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

        if (Schema::hasColumn('recipient_administrations', 'logo')) {
            Schema::table('recipient_administrations', function (Blueprint $table) {
                $table->dropColumn('logo');
            });
        }
    }
};
