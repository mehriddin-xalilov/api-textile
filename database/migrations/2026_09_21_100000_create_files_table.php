<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('avatar_id')->references('id')->on('files')->nullOnDelete();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->text('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropForeign(['avatar_id']));
        Schema::dropIfExists('translations');
        Schema::dropIfExists('files');
    }
};
