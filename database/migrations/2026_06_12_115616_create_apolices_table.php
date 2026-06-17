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
        Schema::create('apolices', function (Blueprint $table) {
            $table->id();

            //fk
            $table->foreignId('segurado_id')
                ->constrained('segurados')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('filial_id')
                ->constrained('filiais')
                ->onDelete('cascade');
            $table->foreignId('cotacao_id')
                ->constrained('cotacoes')
                ->restrictedonDelete();
            $table->foreignId('apolice_origem_id')
                ->nullable()
                ->constrained('apolices')
                ->restrictedonDelete();

            $table->string('numero_apolice')->unique();
            $table->date('data_emissao');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('status', length: 26);

            $table->jsonb('snapshot');
            $table->jsonb('dados_bem_assegurado');
            $table->jsonb('beneficiarios')->nullable();

            $table->string('forma_pagamento', length: 20);
            $table->unsignedInteger('quantidade_parcelas');
            $table->decimal('valor_parcela', 10, 2);
            $table->decimal('valor_total', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apolices');
    }
};
