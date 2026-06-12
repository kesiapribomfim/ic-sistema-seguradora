<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filiais', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cnpj', length: 14)->unique();
            $table->string('telefone', length: 11);
            $table->string('endereco');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('uf', length: 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filiais');
    }
};
