<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** B2B xat yuborilgan kompaniyalar: kim ochdi, kim havolani bosdi, kim ro'yxatdan o'tdi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_leads', function (Blueprint $table) {
            $table->id();
            $table->string('token', 20)->unique();          // xatdagi havola belgisi
            $table->string('company');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('segment', 10)->nullable();
            $table->string('campaign', 50)->default('b2b');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('first_click_at')->nullable();
            $table->timestamp('last_click_at')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->string('last_path')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['campaign', 'first_click_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_leads');
    }
};
