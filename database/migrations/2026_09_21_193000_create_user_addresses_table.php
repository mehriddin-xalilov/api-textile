<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mijozning saqlangan yetkazib berish manzillari (buyurtmada tanlab olinadi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->nullable();          // Uy, Ish, ...
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->string('region')->nullable();             // viloyat
            $table->string('city')->nullable();               // shahar / tuman
            $table->string('street');                         // ko'cha, uy
            $table->string('apartment', 50)->nullable();      // kvartira
            $table->string('landmark')->nullable();           // mo'ljal
            $table->text('note')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
