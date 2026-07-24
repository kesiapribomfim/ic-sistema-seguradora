<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produto extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nome',
        'codigo',
        'ramo',
        'descricao',
        'status',
        'versao',
        'parametros_calculo',
    ];

    protected $casts = [
        'status' => 'boolean',
        'parametros_calculo' => 'array',
    ];

    public function cotacoes(){
        return $this->hasMany(Cotacao::class);
    }

    public function coberturas(){
        return $this->belongsToMany(Cobertura::class, 'cobertura_produto')
        ->withPivot('limite_maximo','obrigatoria')
        ->withTimestamps();
    }


}
