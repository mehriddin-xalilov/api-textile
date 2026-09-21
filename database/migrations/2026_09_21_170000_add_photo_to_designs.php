<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            // Tayyor mahsulot uchun do'kon fotosi (marketplace ko'rinishi). Bo'lmasa 3D preview ishlatiladi.
            $table->foreignId('photo_file_id')->nullable()->after('preview_file_id')->constrained('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('designs', fn (Blueprint $t) => $t->dropConstrainedForeignId('photo_file_id'));
    }
};
