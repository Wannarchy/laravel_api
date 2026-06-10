<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('email', 255)->unique();
            $table->string('mot_de_passe', 255);
            $table->boolean('est_confirme')->default(false);
            $table->string('token_confirmation', 255)->nullable();
            $table->timestamp('date_inscription')->nullable()->useCurrent();
            $table->timestamp('derniere_connexion')->nullable();
            $table->string('token_reinitialisation', 64)->nullable();
            $table->dateTime('expiration_token')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
