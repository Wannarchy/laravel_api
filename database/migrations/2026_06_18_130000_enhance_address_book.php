<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->string('usage_type', 20)->default('both')->after('label');
            $table->boolean('is_default_shipping')->default(false)->after('is_default');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_name', 200)->nullable()->after('billing_address');
            $table->text('shipping_address')->nullable()->after('shipping_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_shipping')->default(false)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['usage_type', 'is_default_shipping']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_name', 'shipping_address']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('requires_shipping');
        });
    }
};
