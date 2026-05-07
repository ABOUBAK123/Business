<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personnel_leave_types')) {
            return;
        }

        $administrations = collect();

        if (Schema::hasTable('issuing_administrations')) {
            $administrations = $administrations->merge(
                DB::table('issuing_administrations')->select('id')->get()->map(fn ($row) => [
                    'type' => 'emitter',
                    'id' => $row->id,
                ])
            );
        }

        if (Schema::hasTable('recipient_administrations')) {
            $administrations = $administrations->merge(
                DB::table('recipient_administrations')->select('id')->get()->map(fn ($row) => [
                    'type' => 'recipient',
                    'id' => $row->id,
                ])
            );
        }

        foreach ($administrations as $administration) {
            $annual = DB::table('personnel_leave_types')
                ->where('administration_type', $administration['type'])
                ->where('administration_id', $administration['id'])
                ->whereRaw("UPPER(COALESCE(code, '')) = 'ANNUAL'")
                ->first();

            if ($annual) {
                DB::table('personnel_leave_types')
                    ->where('id', $annual->id)
                    ->update([
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            $annuel = DB::table('personnel_leave_types')
                ->where('administration_type', $administration['type'])
                ->where('administration_id', $administration['id'])
                ->whereRaw("UPPER(COALESCE(code, '')) = 'ANNUEL'")
                ->first();

            if ($annuel) {
                DB::table('personnel_leave_types')
                    ->where('id', $annuel->id)
                    ->update([
                        'code' => 'ANNUAL',
                        'name' => 'Conge annuel',
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            DB::table('personnel_leave_types')->insert([
                'id' => (string) Str::uuid(),
                'administration_type' => $administration['type'],
                'administration_id' => $administration['id'],
                'code' => 'ANNUAL',
                'name' => 'Conge annuel',
                'description' => 'Conge annuel',
                'unit' => 'day',
                'default_days' => 30,
                'carry_over_days' => 5,
                'requires_attachment' => false,
                'is_paid' => true,
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid deleting user-managed leave types.
    }
};
