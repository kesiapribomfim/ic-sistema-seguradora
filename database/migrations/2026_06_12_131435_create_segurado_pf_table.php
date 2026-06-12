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
        Schema::create('segurado_pf', function (Blueprint $table) {
            $table->id();
            //fk
            $table->foreignId('segurado_id')
                ->constrained('segurados')
                ->onDelete('cascade');
            
            $table->string('cpf', length: 11)->unique();
            $table->string('rg', length: 20)->unique();
            $table->string('nome');
            $table->date('data_nascimento');
            $table->string('profissao');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segurado_pf');
    }
};
