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
        Schema::create('cobertura_produto', function (Blueprint $table) {
            //fk
            $table->foreignId('cobertura_id')
                ->constrained('coberturas')
                ->onDelete('cascade');
            $table->foreignId('produto_id')
                ->constrained('produtos')
                ->onDelete('cascade');

            $table->decimal('limite_maximo', 10, 2)
                ->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobertura_produto');
    }
};
