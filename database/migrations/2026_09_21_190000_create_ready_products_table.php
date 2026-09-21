<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tayyor mahsulot — admin panelda rasm bilan kiritiladigan, allaqachon bosilgan mahsulot.
 * Konstruktorga bog'liq emas: dizayn ham, 3D model ham kerak emas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ready_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('old_price', 12, 2)->nullable();
            $table->string('color_name')->nullable();
            $table->string('color_hex', 9)->nullable();
            $table->json('sizes')->nullable();              // ["S","M","L"]
            $table->unsignedInteger('quantity')->default(0); // umumiy qoldiq (razmerlarsiz, MVP)
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort']);
        });

        Schema::create('ready_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ready_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('ready_product_id')->nullable()->after('design_id')->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ready_product_id');
        });
        Schema::dropIfExists('ready_product_images');
        Schema::dropIfExists('ready_products');
    }
};
