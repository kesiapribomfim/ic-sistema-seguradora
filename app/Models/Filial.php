<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Filial extends Model
{   
    use HasFactory;

    protected $table = 'filiais';

    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'rua',
        'numero',
        'bairro',
        'complemento',
        'cidade',
        'uf',
        'cep',
    ];

    protected $casts = [
        'telefone' => 'string',
    ];
    
    //Many-to-Many relationship with User model
    public function users()
    {
        return $this->belongsToMany(User::class,'filial_user')
        ->withPivot('perfil_acesso')
        ->withTimestamps();
    }

    public function cotacoes(){
        return $this->hasMany(Cotacao::class); //uma filial tem muitas cotações
    }

    public function apolices()
    {
        return $this->hasMany(Apolice::class);
    }

    /**
     * Sinistros ocorridos na carteira desta filial.
     */
    public function sinistros(): HasManyThrough
    {
        // Como o sinistro geralmente pertence à apólice, e a apólice à filial,
        // o hasManyThrough faz a ponte automática, buscando todos os sinistros
        // passando pelas apólices dessa filial.
        // Se a sua tabela 'sinistros' tiver uma coluna 'filial_id', mude para hasMany(Sinistro::class).
        return $this->hasManyThrough(Sinistro::class, Apolice::class);
    }


    /**
     * Carteira de Segurados (Clientes) desta filial.
     * Como o cadastro é centralizado, a ligação ocorre através das apólices.
     */
    public function segurados(): BelongsToMany
    {
        return $this->belongsToMany(
            Segurado::class, 
            'apolices',      // Usamos a tabela de apólices como "ponte"
            'filial_id',     // A chave da filial na tabela de apólices
            'segurado_id'    // A chave do segurado na tabela de apólices
        )->distinct();       // O ->distinct() garante que se o cliente tiver 3 apólices nessa filial, ele apareça na lista apenas uma vez.
    }
}
