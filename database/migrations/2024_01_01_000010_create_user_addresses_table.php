<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->index();
            $table->string('label', 80)->default('Adresse');
            $table->string('prenom', 80);
            $table->string('nom', 80);
            $table->string('adresse1', 200);
            $table->string('adresse2', 200)->default('');
            $table->string('ville', 100);
            $table->string('region', 100)->default('');
            $table->string('code_postal', 20);
            $table->string('pays', 80)->default('France');
            $table->string('telephone', 30)->default('');
            $table->boolean('is_default')->default(false);
            $table->dateTime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
