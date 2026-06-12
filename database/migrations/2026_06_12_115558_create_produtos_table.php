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
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('codigo', length: 10)->unique();
            $table->string('ramo', length: 11); //auto, vida ou residencial
            
            $table->text('descricao');
            $table->text('lista_resumida');
            $table->boolean('status')->default(true);
            $table->string('versao', length: 15);

            $table->jsonb('coberturas');
            $table->jsonb('parametros_calculo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
