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
        Schema::table('cobertura_produto', function (Blueprint $table) {
            $table->boolean('obrigatoria')->default(true)->after('limite_maximo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobertura_produto', function (Blueprint $table) {
            $table->dropColumn('obrigatoria');
        });
    }
};
