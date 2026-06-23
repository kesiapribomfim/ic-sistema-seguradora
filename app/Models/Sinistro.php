<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sinistro extends Model
{
    use HasFactory;

    protected $fillable = [
        'apolice_id',
        'data_hora_ocorrencia',

        'rua',
        'numero',
        'bairro',
        'complemento',
        'cidade',
        'uf',
        'cep',
        
        'descricao',
        'coberturas_envolvidas',
        'status',
        'valor_indenizacao',
        'valor_pago',
    ];

    protected $casts = [
    'data_hora_ocorrencia' => 'datetime',
    'coberturas_envolvidas' => 'array',
];

    //relacionamentos

    public function apolice(){
        return $this->belongsTo(Apolice::class);
    }

    public function movimentacoes() {
        return $this->hasMany(SinistroMovimentacao::class);
    }

}
