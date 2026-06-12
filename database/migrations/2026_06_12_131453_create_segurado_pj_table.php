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
        Schema::create('segurado_pj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segurado_id')
                ->constrained('segurados')
                ->onDelete('cascade');
            
            $table->string('cnpj', length: 14)->unique();
            $table->string('razao_social');
            $table->string('inscricao_estadual', length:14)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segurado_pj');
    }
};
