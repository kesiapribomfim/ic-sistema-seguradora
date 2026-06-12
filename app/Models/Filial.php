<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filial extends Model
{
    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'endereco',
        'bairro',
        'cidade',
        'uf',
    ];

    protected $casts = [
        'telefone' => 'string',
    ];
}
