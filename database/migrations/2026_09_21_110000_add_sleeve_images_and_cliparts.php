<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Har rang uchun 4 ta ko'rinish: old, orqa, chap yeng, o'ng yeng (konstruktorda "aylantirish")
        Schema::table('product_colors', function (Blueprint $table) {
            $table->foreignId('left_image_id')->nullable()->after('back_image_id')->constrained('files')->nullOnDelete();
            $table->foreignId('right_image_id')->nullable()->after('left_image_id')->constrained('files')->nullOnDelete();
        });

        // Tayyor logolar kutubxonasi (SVG — rangi o'zgartiriladi)
        Schema::create('cliparts', function (Blueprint $table) {
            $table->id();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('category', 64)->default('general')->index(); // sport, brand, text, emoji...
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->boolean('recolorable')->default(true);   // bir rangli SVG — fill almashadi
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliparts');
        Schema::table('product_colors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('left_image_id');
            $table->dropConstrainedForeignId('right_image_id');
        });
    }
};
