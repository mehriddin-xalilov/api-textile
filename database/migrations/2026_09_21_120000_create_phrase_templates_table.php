<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trenddagi so'zlar / gaplar: tayyor uslubdagi matn shablonlari (konstruktorda 1 bosishda tushadi, tahrirlanadi)
        Schema::create('phrase_templates', function (Blueprint $table) {
            $table->id();
            $table->string('text', 120);
            $table->string('category', 64)->default('trend')->index(); // trend, motivation, humor, love, uzbek, sport
            $table->string('font_family', 64);
            $table->string('font_weight', 3)->default('700');
            $table->string('font_style', 8)->default('normal');
            $table->string('fill', 7)->default('#111111');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phrase_templates');
    }
};
