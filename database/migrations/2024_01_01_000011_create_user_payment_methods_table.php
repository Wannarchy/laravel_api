<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->index();
            $table->string('card_holder', 120);
            $table->char('card_last4', 4);
            $table->string('card_brand', 20)->default('Visa');
            $table->tinyInteger('exp_month');
            $table->smallInteger('exp_year');
            $table->boolean('is_default')->default(false);
            $table->dateTime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
