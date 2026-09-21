<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 3D konstruktor uchun GLB model fayli (web-3d/public/models/ ichida). null → shirt_model.glb
            $table->string('model_file', 120)->nullable()->after('gender');
            // Modeldagi bosma zonalar (decal position/rotation/scale) — model bilan birga keladi
            $table->json('model_zones')->nullable()->after('model_file');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['model_file', 'model_zones']));
    }
};
