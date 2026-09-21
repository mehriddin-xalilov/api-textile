<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_color_id')->constrained('product_colors')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->json('canvas');                                   // konstruktor holati (qatlamlar, koordinatalar)
            $table->foreignId('preview_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('print_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('status', 16)->default('draft')->index(); // draft | ready | archived
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();                  // ORD-2026-000001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('new')->index();
            $table->string('payment_status', 16)->default('unpaid')->index();
            $table->string('payment_method', 16)->nullable();       // cash | payme | click | uzum
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('UZS');
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('delivery_address');
            $table->text('note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            // Snapshot: mahsulot keyin o'zgarsa ham buyurtma tarixi buzilmaydi
            $table->string('product_name');
            $table->string('color_name', 64);
            $table->string('color_hex', 7);
            $table->string('size_name', 16);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('print_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            $table->foreignId('print_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->string('comment')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('designs');
    }
};
