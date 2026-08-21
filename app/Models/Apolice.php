<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apolice extends Model
{
    use HasFactory;

   protected $fillable = [
        'segurado_id',
        'user_id',
        'filial_id',
        'cotacao_id',
        'apolice_origem_id',
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
        'data_emissao' =>'date',
        'data_inicio' => 'date',
        'data_fim'=> 'date',
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
        return $this->belongsTo(Cotacao::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function apoliceOrigem()
    {
        return $this->belongsTo(Apolice::class, 'apolice_origem_id');
    }

    public function renovacoes()
    {
        return $this->hasMany(Apolice::class, 'apolice_origem_id');
    }

    public function beneficiarios()
    {
        return $this->belongsToMany(Beneficiario::class, 'apolice_beneficiario')
            ->withPivot('percentual_rateio', 'parentesco')
            ->withTimestamps();
    }
}

