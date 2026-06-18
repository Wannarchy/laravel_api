<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_logs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->unsignedInteger('user_id')->index();
            $table->string('action', 80)->default('account.self_deleted');
            $table->dateTime('created_at')->nullable()->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_logs');
    }
};
