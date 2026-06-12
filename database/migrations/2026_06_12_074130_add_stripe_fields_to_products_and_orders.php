<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('stripe_product_id', 120)->nullable()->after('price_yearly');
            $table->string('stripe_price_id_monthly', 120)->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_yearly', 120)->nullable()->after('stripe_price_id_monthly');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id', 120)->nullable()->after('stripe_payment_intent');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_product_id',
                'stripe_price_id_monthly',
                'stripe_price_id_yearly',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
