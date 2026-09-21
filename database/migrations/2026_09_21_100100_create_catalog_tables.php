<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->foreignId('image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name_uz', 64);
            $table->string('name_ru', 64)->nullable();
            $table->string('name_en', 64)->nullable();
            $table->string('hex', 7);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 16)->unique(); // XS, S, M, L, XL, XXL
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            $table->text('description_en')->nullable();
            $table->string('fabric', 120)->nullable();          // 100% paxta, 240 gsm
            $table->string('origin_country', 2)->nullable();    // CN | TR
            $table->string('gender', 8)->default('unisex');     // male | female | unisex | kids
            $table->decimal('base_price', 12, 2);
            $table->decimal('print_price', 12, 2)->default(0);  // logo bosish narxi
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2)->nullable(); // null → product.base_price
            $table->foreignId('front_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('back_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->unique(['product_id', 'color_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_color_id')->constrained('product_colors')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->integer('quantity')->default(0);   // ombordagi jami
            $table->integer('reserved')->default(0);   // buyurtmalarga band qilingan
            $table->timestamps();
            $table->unique(['product_color_id', 'size_id']);
            $table->index(['product_id', 'quantity']);
        });

        Schema::create('print_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('side', 16);          // front | back | left_sleeve | right_sleeve
            $table->string('name', 64);          // "Ko'krak chap", "Orqa markaz"
            $table->decimal('x', 5, 2);          // mockup rasmiga nisbatan foiz
            $table->decimal('y', 5, 2);
            $table->decimal('width', 5, 2);
            $table->decimal('height', 5, 2);
            $table->decimal('max_width_cm', 5, 1)->nullable();
            $table->decimal('max_height_cm', 5, 1)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_areas');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('products');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('categories');
    }
};
