<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Konstruktorga kirish: admin tasdiqlagan vaqt (null = tasdiqlanmagan)
            $table->timestamp('studio_approved_at')->nullable()->after('phone_verified_at');
        });

        // Hozirgi foydalanuvchilar (xodimlar va mavjud mijozlar) tasdiqlangan hisoblanadi
        DB::table('users')->update(['studio_approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('studio_approved_at'));
    }
};
