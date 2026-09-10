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


//TODO: Adicionar atributo taxa_base, tirando do JSONB de parametros_calculo
// Migration: Criar uma nova migration (add_taxa_base_to_produtos_table).
// Model: Adicionar ao $fillable e colocar um $casts de float.
// Seeder: Tirar o valor de dentro do array JSON e passar para a coluna nova.
// Filament Resource: Mudar o campo visual (provavelmente tirando de dentro de um KeyValue ou Repeater e virando um TextInput normal).
