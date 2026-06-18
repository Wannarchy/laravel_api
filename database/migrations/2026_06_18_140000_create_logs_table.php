<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('actor_type', 10)->index();
            $table->integer('admin_id')->nullable()->index();
            $table->integer('user_id')->nullable()->index();
            $table->string('action', 120)->index();
            $table->string('target_type', 80)->nullable()->index();
            $table->integer('target_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->json('details')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent()->index();

            $table->index(['target_type', 'target_id'], 'idx_logs_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
