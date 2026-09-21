<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mahsulot xususiyatlari (`specs`): [{name, value}] — sahifada jadval ko'rinishida chiqadi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ready_products', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('sizes');
            $table->unsignedInteger('sold_count')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('ready_products', function (Blueprint $table) {
            $table->dropColumn(['specs', 'sold_count']);
        });
    }
};
