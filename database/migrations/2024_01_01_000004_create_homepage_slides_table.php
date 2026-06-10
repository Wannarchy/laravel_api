<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_slides', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('title', 200);
            $table->string('subtitle', 500)->nullable();
            $table->string('image_path', 255)->default('logo.jpg');
            $table->string('link_url', 255)->nullable();
            $table->integer('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_slides');
    }
};
