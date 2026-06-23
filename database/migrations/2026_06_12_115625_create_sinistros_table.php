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
        Schema::create('sinistros', function (Blueprint $table) {
            $table->id();
            //fk
            $table->foreignId('apolice_id')
                ->constrained('apolices')
                ->restrictOnDelete();
            
            $table->dateTime('data_hora_ocorrencia');

            $table->string('rua');
            $table->string('numero', length: 20);
            $table->string('bairro');
            $table->string('complemento',100) ->nullable();
            $table->string('cidade');
            $table->string('uf', length: 2);
            $table->string('cep', length: 8);

            $table->text('descricao');
            
            $table->jsonb('coberturas_envolvidas');
            $table->string('status', length: 20); //Em analise, em perícia, aprovado, negado, pago, encerrado

            $table->decimal('valor_indenizacao', 10, 2)->nullable();
            $table->decimal('valor_pago', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sinistros');
    }
};
