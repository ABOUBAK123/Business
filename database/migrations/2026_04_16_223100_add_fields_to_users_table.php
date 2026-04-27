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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 255)->nullable()->after('name');
            $table->string('avatar', 500)->nullable();
            $table->enum('role', ['admin', 'user', 'signer', 'manager'])->default('user');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->string('quota', 50)->default('5 Go')->nullable();
            $table->text('bio')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'avatar', 'role', 'status', 'quota', 'bio']);
            $table->dropSoftDeletes();
        });
    }
};
