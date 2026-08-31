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
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('valor_alcada_aprovacao', 12, 2)
                ->nullable()
                ->comment('Valor máximo de indenização que um analista pode aprovar sem o gestor');
        });

        Schema::table('sinistros', function (Blueprint $table) {
            $table->foreignId('aprovado_gestor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('ID do gestor que realizou a dupla aprovação');

            $table->timestamp('data_aprovacao_gestor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sinistros', function (Blueprint $table) {
            $table->dropForeign(['aprovado_gestor_id']);
            $table->dropColumn(['aprovado_gestor_id', 'data_aprovacao_gestor']);
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('valor_alcada_aprovacao');
        });
    }
};
