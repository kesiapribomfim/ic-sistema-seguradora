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
        Schema::create('sinistro_movimentacoes', function (Blueprint $table) {
            $table->id();
            //fk
            $table->foreignId('sinistro_id')
                ->constrained('sinistros')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('descricao');
            $table->string('acao_realizada', length: 50); //Ex: Análise, Perícia, Aprovação, Negação, Pagamento, Encerramento
            $table->jsonb('anexos')->nullable(); //Para armazenar evidências, laudos, fotos, etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sinistro_movimentacoes');
    }
};
