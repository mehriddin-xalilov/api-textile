<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();       // BATCH-2026-0001
            $table->string('supplier_country', 2);         // CN | TR
            $table->string('supplier_name')->nullable();
            $table->date('arrived_at')->nullable();
            $table->string('status', 16)->default('draft')->index(); // draft | received
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['inventory_batch_id', 'product_variant_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);                  // in | out | reserve | release | adjust
            $table->integer('quantity');                 // ishorali: + kirim, - chiqim
            $table->nullableMorphs('reference');         // batch, order ...
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_batch_items');
        Schema::dropIfExists('inventory_batches');
    }
};
