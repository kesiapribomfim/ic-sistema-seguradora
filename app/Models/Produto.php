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
        'valor_alcada',
        'valor_alcada_aprovacao' //atributo específico para sinistros
    ];

    protected $casts = [
        'status' => 'boolean',
        'parametros_calculo' => 'array',
        'valor_alcada' => 'decimal:2',
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
