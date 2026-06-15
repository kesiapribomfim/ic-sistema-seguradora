<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Filial extends Model
{   
    use HasFactory;

    protected $table = 'filiais';

    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'cep',
    ];

    protected $casts = [
        'telefone' => 'string',
    ];
}
