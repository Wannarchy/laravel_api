<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->json('technical_specs')->nullable()->after('description');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->index();
            $table->string('image_path', 512);
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'technical_specs']);
        });
    }
};
