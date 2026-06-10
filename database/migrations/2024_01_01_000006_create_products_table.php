<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('category_id')->nullable()->index();
            $table->string('name', 200)->index();
            $table->string('image_path', 255)->default('logo.jpg');
            $table->decimal('price_monthly', 10, 2)->default(0.00);
            $table->decimal('price_yearly', 10, 2)->default(0.00);
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_order')->default(999);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['is_available', 'is_featured', 'featured_order'], 'idx_products_sort');
            $table->index('price_monthly', 'idx_products_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
