<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // blank — logosiz (mijoz dizayn qiladi); finished — tayyor brendli dizayn (shablon bilan sotiladi)
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 16)->default('blank')->after('gender')->index();
        });

        // Tayyor dizayn (shablon): admin belgilaydi, mijoz ochib o'zgartirib buyurtma beradi
        Schema::table('designs', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('status')->index();
            $table->string('template_title')->nullable()->after('is_template');
            $table->unsignedSmallInteger('sort')->default(0)->after('template_title');
        });

        // To'lov tranzaksiyalari (Payme / Click / Uzum)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16)->index();                 // payme | click | uzum
            $table->string('provider_transaction_id', 64)->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('state', 16)->default('created')->index(); // created | performed | cancelled
            $table->json('payload')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->smallInteger('cancel_reason')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::table('designs', fn (Blueprint $t) => $t->dropColumn(['is_template', 'template_title', 'sort']));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('type'));
    }
};
