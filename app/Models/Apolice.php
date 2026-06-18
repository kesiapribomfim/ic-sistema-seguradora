<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apolice extends Model
{
    use HasFactory;

   protected $fillable = [
        'numero_apolice',
        'data_emissao',
        'data_inicio',
        'data_fim',
        'status',
        'snapshot',
        'dados_bem_assegurado',
        'beneficiarios',
        'forma_pagamento',
        'quantidade_parcelas',
        'valor_parcela',
        'valor_total',
    ];

    protected $casts = [
        'snapshot'=> 'array',
        'dados_bem_assegurado'=>'array',
        'beneficiarios'=>'array',
    ];


    public function segurado(){
        return $this->belongsTo(Segurado::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function filial(){
        return $this->belongsTo(Filial::class);
    }

    public function cotacao()
    {
        return $this->hasOne(Cotacao::class);
    }

    public function apolice(){
        return $this->hasOne(Apolice::class);
    }
}

