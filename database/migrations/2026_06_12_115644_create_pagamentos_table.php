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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('apolice_id')
                ->constrained('apolices')
                ->restrictedOnDelete();
            $table->foreignId('sinistro_id')
                ->nullable()
                ->constrained('sinistros')
                ->restrictedOnDelete();
            
            $table->string('tipo_movimentacao', length:30); // Ex: 'Recebimento', 'Pagamento Indenização'
            $table->decimal('valor', 10,2);
            $table->integer('num_parcela')
                ->nullable();
            $table->date('data_vencimento');
            $table->date('data_pagamento')
                ->nullable();
            $table->string('status',length:15); //'Aberta, Paga, Vencida, Cancelada'
            $table->string('caminho_fatura_pdf')
                ->nullable();
            $table->string('metodo_baixa', 20)
                ->nullable(); // Ex: 'Manual', 'Automática'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
