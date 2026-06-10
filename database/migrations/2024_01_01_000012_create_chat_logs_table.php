<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->nullable()->index();
            $table->string('session_id', 100)->nullable();
            $table->text('user_message');
            $table->text('bot_response');
            $table->dateTime('created_at')->nullable()->useCurrent();

            $table->index('created_at');
            $table->index(['session_id', 'created_at'], 'idx_chat_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};
