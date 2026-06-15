<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Segurado extends Model
{   use HasFactory;

        protected $fillable = [
            'nome',
            'cpf',
            'tipo',
            'telefone',
            'email',
            'endereco',
            'bairro',
            'cidade',
            'uf',
            'cep',
            'score',
            'status',
        ];

        protected $casts = [
            'telefone' => 'string',
            'cpf' => 'string',
            'score' => 'integer',
        ];
}
