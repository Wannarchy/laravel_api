<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('subscriptions', 'product_subscriptions');

        Schema::table('product_subscriptions', function (Blueprint $table) {
            $table->string('stripe_subscription_id', 120)->nullable()->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('product_subscriptions', function (Blueprint $table) {
            $table->dropColumn('stripe_subscription_id');
        });

        Schema::rename('product_subscriptions', 'subscriptions');
    }
};
