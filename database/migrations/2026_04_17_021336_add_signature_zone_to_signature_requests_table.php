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
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('zone_page')->default(1)->after('expiry_date')->comment('Numéro de page (1-based)');
            $table->float('zone_x', 8, 4)->nullable()->after('zone_page')->comment('Position X en % de la largeur de la page');
            $table->float('zone_y', 8, 4)->nullable()->after('zone_x')->comment('Position Y en % de la hauteur de la page');
            $table->float('zone_width', 8, 4)->nullable()->after('zone_y')->comment('Largeur en % de la largeur de la page');
            $table->float('zone_height', 8, 4)->nullable()->after('zone_width')->comment('Hauteur en % de la hauteur de la page');
            $table->string('zone_label')->nullable()->after('zone_height')->comment('Texte affiché dans la zone (ex: nom du signataire)');
        });
    }

    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn(['zone_page', 'zone_x', 'zone_y', 'zone_width', 'zone_height', 'zone_label']);
        });
    }
};
