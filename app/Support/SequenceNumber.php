<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Yil kesimida ketma-ket raqam: ORD-2026-000001, BATCH-2026-0001.
 * Postgres advisory lock — bir vaqtda ikki so'rov bir xil raqam olmaydi.
 * Tranzaksiya ichida chaqirilishi shart (xact lock tranzaksiya tugaguncha ushlanadi).
 */
final class SequenceNumber
{
    public static function next(string $table, string $prefix, int $pad): string
    {
        $year = now()->year;

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('select pg_advisory_xact_lock(?)', [crc32("seq:{$table}")]);
        }

        $count = DB::table($table)->whereYear('created_at', $year)->count() + 1;

        return sprintf("%s-%d-%0{$pad}d", $prefix, $year, $count);
    }
}
