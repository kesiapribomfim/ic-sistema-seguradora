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
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->id();
            //fk
            $table->foreignId('segurado_id')
                ->constrained('segurados')
                ->onDelete('cascade');
            $table->foreignId('produto_id')
                ->constrained('produtos')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('filial_id')
                ->constrained('filiais')
                ->onDelete('cascade');

            $table->jsonb('dados_especificos')
                ->nullable(); //dados específicos do produto, como marca e modelo para veículos, ou cobertura para residências
            $table->jsonb('cobertura_selecionada')
                ->nullable(); //cobertura selecionada pelo cliente, caso seja um produto com várias coberturas
            $table->string('status', length: 30)
                ->default('Em Elaboração'); //em elaboração, enviado ao cliente, aceita, recusada, expirada

            $table->decimal('valor_total', 10, 2)
                ->nullable(); //valor total da cotação, caso seja um produto com várias coberturas
            $table->date('validade')
                ->nullable(); //validade da cotação, caso seja um produto com várias coberturas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotacoes');
    }
};
