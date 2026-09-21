<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3D kiyim modellari (GLB). Admin yuklaydi, mahsulotga bog'laydi. Konstruktor shu faylni yuklaydi.
        Schema::create('garment_models', function (Blueprint $table) {
            $table->id();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();          // .glb
            $table->foreignId('thumbnail_id')->nullable()->constrained('files')->nullOnDelete();
            $table->json('zones')->nullable();   // {front:{position,rotation,scale}, back, sleeve_left, sleeve_right}
            $table->string('author')->nullable(); // litsenziya (CC-BY) uchun muallif
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('garment_model_id')->nullable()->after('gender')->constrained('garment_models')->nullOnDelete();
            $table->dropColumn(['model_file', 'model_zones']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('garment_model_id');
            $table->string('model_file', 120)->nullable();
            $table->json('model_zones')->nullable();
        });
        Schema::dropIfExists('garment_models');
    }
};
