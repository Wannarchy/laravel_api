<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 150);
            $table->string('image_path', 255)->default('logo.jpg');
            $table->integer('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
