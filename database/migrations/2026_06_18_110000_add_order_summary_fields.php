<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('total');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('promo_discount', 10, 2)->default(0)->after('tax_amount');
            $table->string('promo_code', 50)->nullable()->after('promo_discount');
            $table->string('payment_brand', 40)->nullable()->after('card_last4');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'tax_amount',
                'promo_discount',
                'promo_code',
                'payment_brand',
            ]);
        });
    }
};
