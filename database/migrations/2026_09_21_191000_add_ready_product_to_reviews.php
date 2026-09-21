<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sharh tayyor mahsulotga ham yozilishi mumkin (konstruktor mahsulotidan mustaqil). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('ready_product_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->change();
            $table->index(['ready_product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ready_product_id');
        });
    }
};
