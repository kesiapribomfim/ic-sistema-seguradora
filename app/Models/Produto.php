<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produto extends Model
{
    use HasFactory;
    
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
