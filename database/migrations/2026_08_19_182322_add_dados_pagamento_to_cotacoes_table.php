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
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->string('forma_pagamento_preferida')->nullable()->after('status');
            $table->integer('quantidade_parcelas_preferida')->nullable()->after('forma_pagamento_preferida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            //
        });
    }
};
