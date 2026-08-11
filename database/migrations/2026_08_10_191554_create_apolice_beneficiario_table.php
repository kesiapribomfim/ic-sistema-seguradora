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
        Schema::create('apolice_beneficiario', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('apolice_id')
                ->constrained('apolices')
                ->restrictOnDelete();
            $table->foreignId('beneficiario_id')
                ->constrained('beneficiarios')
                ->restrictOnDelete();
                
            $table->string('percentual_rateio', 5, 2);
            $table->string('parentesco')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apolice_beneficiario');
    }
};
