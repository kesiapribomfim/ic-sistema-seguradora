<?php

namespace App\Models;

use App\Observers\CotacaoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[ObservedBy(CotacaoObserver::class)]
class Cotacao extends Model
{
    use HasFactory;

    protected $table = 'cotacoes';

    protected $fillable = [
        'segurado_id',
        'produto_id',
        'user_id',
        'filial_id',
        'dados_especificos',
        'observacao_cliente',
        'cobertura_selecionada',
        'status',
        'valor_total',
        'validade',
        'forma_pagamento_preferida',
        'quantidade_parcelas_preferida',
    ];

    protected $casts = [
        'dados_especificos' => 'array',
        'cobertura_selecionada' => 'array',
    ];

    protected static function booted()
    {
        // Toda vez que uma cotação for criada, o Laravel injeta um UUID nela
        static::creating(function ($cotacao) {
            $cotacao->uuid = (string) Str::uuid();
        });
    }

    //constants
    public const STATUS_ELABORACAO = 'Em Elaboração';
    public const STATUS_ENVIADA = 'Enviada ao Cliente';
    public const STATUS_EM_SUBSCRICAO = 'Em Subscrição';
    public const STATUS_ACEITA = 'Aceita';
    public const STATUS_RECUSADA = 'Recusada';
    public const STATUS_EXPIRADA = 'Expirada';

    // fk
    public function segurado()
    {
        return $this->belongsTo(Segurado::class); // muitos para um segurado
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class); // muitos para um produto
    }

    public function user()
    {
        return $this->belongsTo(User::class); // muitos para um usuario
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class); // muitos para uma filial
    }

    public function apolice()
    {
        return $this->hasOne(Apolice::class); // uma para uma apolice
    }

    public function gerarLinkCheckout()
    {
        return URL::temporarySignedRoute(
            'checkout.cotacao',
            now()->addDays(30), // puxar a validade
            ['cotacao' => $this] // aponta para o uuid das cotações
        );
    }
}
