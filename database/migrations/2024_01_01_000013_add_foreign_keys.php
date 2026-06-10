<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id', 'fk_product_category')
                ->references('id')
                ->on('categories')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_order_user')
                ->references('id')
                ->on('utilisateurs')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id', 'fk_item_order')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('product_id', 'fk_item_product')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign('fk_item_product');
            $table->dropForeign('fk_item_order');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_order_user');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('fk_product_category');
        });
    }
};
