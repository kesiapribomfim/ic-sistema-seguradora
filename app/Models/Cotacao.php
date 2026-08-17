<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

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
        'cobertura_selecionada',
        'status',
        'valor_total',
        'validade',
    ];

    protected $casts =[
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

    //fk
    public function segurado() {
        return $this->belongsTo(Segurado::class); //muitos para um segurado
    }

    public function produto() {
        return $this->belongsTo(Produto::class); //muitos para um produto
    }

    public function user() {
        return $this->belongsTo(User::class);//muitos para um usuario
    }

    public function filial() {
        return $this->belongsTo(Filial::class); //muitos para uma filial
    }

    public function apolice() {
        return $this->hasOne(Apolice::class); //uma para uma apolice
    }

    public function gerarLinkCheckout () {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'checkout.cotacao',
            now()->addDays(30), //puxar a validade
            ['cotacao' => $this] //aponta para o uuid das cotações
        );
    }
}