<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bosh sahifa bannerlari (hero slayder)
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title_uz');
            $table->string('title_ru')->nullable();
            $table->string('title_en')->nullable();
            $table->string('subtitle_uz', 500)->nullable();
            $table->string('subtitle_ru', 500)->nullable();
            $table->string('subtitle_en', 500)->nullable();
            $table->string('button_text_uz', 64)->nullable();
            $table->string('button_text_ru', 64)->nullable();
            $table->string('button_text_en', 64)->nullable();
            $table->string('link')->nullable();               // /studio, /product/polo-classic, https://...
            $table->foreignId('image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        // Statik sahifalar: biz-haqimizda, aloqa, yetkazib-berish, qaytarish, oferta...
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('title_uz');
            $table->string('title_ru')->nullable();
            $table->string('title_en')->nullable();
            $table->text('content_uz')->nullable();   // HTML (CKEditor)
            $table->text('content_ru')->nullable();
            $table->text('content_en')->nullable();
            $table->boolean('in_footer')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        // Sayt sozlamalari: telefon, telegram, manzil, ish vaqti, ijtimoiy tarmoqlar... (key-value)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->string('group', 32)->default('contact')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('banners');
    }
};
