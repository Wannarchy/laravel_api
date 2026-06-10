<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->index();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->string('cycle', 20)->default('monthly');
            $table->decimal('price', 10, 2);
            $table->string('status', 20)->default('active')->index();
            $table->date('start_date');
            $table->date('next_billing')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->boolean('renewal_notified')->default(false);

            $table->index(['user_id', 'status'], 'idx_subscriptions_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
