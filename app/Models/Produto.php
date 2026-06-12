<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'ramo',
        'descricao',
        'lista_resumida',
        'status',
        'versao',
        'coberturas',
        'parametros_calculo',
    ];

    protected $casts = [
        'status' => 'boolean',
        'coberturas' => 'array',
        'parametros_calculo' => 'array',
    ];
}
