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
        Schema::create('segurados', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', length: 4);
            $table->string('telefone', length: 11);
            $table->string('email')->unique();

            $table->string('endereco');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('uf', length: 2);
            $table->string('cep', length: 8);

            $table->unsignedInteger('score'); 
            $table->boolean('status')->default(true);

            //fk
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segurados');
    }
};
