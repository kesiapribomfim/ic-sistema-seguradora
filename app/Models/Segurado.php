<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segurado extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'telefone',
        'email',
        'rua',
        'numero',
        'bairro',
        'complemento',
        'cidade',
        'uf',
        'cep',
        'score',
        'status',
        'user_id', 
        'corretor_id',
    ];

    protected $casts = [
        'telefone' => 'string',
        'score' => 'integer',
    ];

    public function seguradoPj()
    {
        return $this->hasOne(SeguradoPj::class); // um segurado tem um tipo pj
    }

    public function seguradoPf()
    {
        return $this->hasOne(SeguradoPf::class); // um seguraod tem um tipo pf
    }

    public function corretor()
    {
        return $this->belongsTo(User::class, 'corretor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class);
    }

    public function apolices()
    {
        return $this->hasMany(Apolice::class);
    }

    public function sinistros()
    {
        return $this->hasManyThrough(Sinistro::class, Apolice::class);
    }

    // filtragem de dados por filial
    public function scopePorFiliais($query, array $filiaisIds)
    {
        return $query->where(function($q) use ($filiaisIds){
            $q->whereHas('corretor.filiais', fn ($q2) => $q2->whereIn('filiais.id', $filiaisIds))
              ->orWhereHas('apolices', fn ($q3) => $q3->whereIn('filial_id', $filiaisIds))
              ->orWhereHas('cotacoes', fn ($q4) => $q4->whereIn('filial_id', $filiaisIds))
              ->orWhereHas('user.filiais', fn ($q5) => $q5->whereIn('filiais.id', $filiaisIds));
        });
    }
}