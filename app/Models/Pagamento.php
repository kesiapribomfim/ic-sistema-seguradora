<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\PagamentoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([PagamentoObserver::class])]
class Pagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'apolice_id',
        'sinistro_id',
        'tipo_movimentacao',
        'valor',
        'num_parcela',
        'data_vencimento',
        'data_pagamento',
        'status',
        'caminho_fatura_pdf',
        'metodo_baixa',

    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'valor' => 'decimal:2',
    ];

    public function apolice()
    {
        return $this->belongsTo(Apolice::class);
    }

    public function sinistro()
    {
        return $this->belongsTo(Sinistro::class);
    }


}
