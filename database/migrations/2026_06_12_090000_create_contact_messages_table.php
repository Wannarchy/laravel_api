<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->nullable()->index();
            $table->string('email', 255);
            $table->string('sujet', 255);
            $table->text('message');
            $table->dateTime('created_at')->nullable()->useCurrent();

            $table->index('created_at');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
