<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('act_request_submissions')) {
            return;
        }

        Schema::table('act_request_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('act_request_submissions', 'tracking_number')) {
                $table->string('tracking_number', 40)->nullable()->after('id');
            }

            if (!Schema::hasColumn('act_request_submissions', 'tracking_token')) {
                $table->string('tracking_token', 80)->nullable()->after('tracking_number');
            }
        });

        $rows = DB::table('act_request_submissions')
            ->select('id', 'created_at', 'tracking_number', 'tracking_token')
            ->whereNull('tracking_number')
            ->orWhereNull('tracking_token')
            ->get();

        foreach ($rows as $row) {
            $createdAt = $row->created_at ? date('Ym', strtotime((string) $row->created_at)) : date('Ym');
            $prefix = 'DACT-' . $createdAt . '-';

            $trackingNumber = (string) ($row->tracking_number ?? '');
            if ($trackingNumber === '') {
                do {
                    $trackingNumber = $prefix . random_int(100000, 999999);
                    $exists = DB::table('act_request_submissions')
                        ->where('tracking_number', $trackingNumber)
                        ->where('id', '!=', $row->id)
                        ->exists();
                } while ($exists);
            }

            $trackingToken = (string) ($row->tracking_token ?? '');
            if ($trackingToken === '') {
                do {
                    $trackingToken = strtolower(Str::random(48));
                    $exists = DB::table('act_request_submissions')
                        ->where('tracking_token', $trackingToken)
                        ->where('id', '!=', $row->id)
                        ->exists();
                } while ($exists);
            }

            DB::table('act_request_submissions')
                ->where('id', $row->id)
                ->update([
                    'tracking_number' => $trackingNumber,
                    'tracking_token' => $trackingToken,
                ]);
        }
    }

    public function down(): void
    {
        // No-op: migration de rattrapage pour environnements divergents.
    }
};
