<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->nullable()->index();
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('billing_name', 200);
            $table->text('billing_address');
            $table->string('stripe_payment_intent', 120)->nullable();
            $table->char('card_last4', 4)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('created_at', 'idx_orders_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
