<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cobertura extends Model
{
    /** @use HasFactory<\Database\Factories\CoberturaFactory> */
    use HasFactory;

    protected $fillable = [
        'ramo',
        'nome',
        'descricao',
    ];

    public function produtos (){
        return $this->belongsToMany(Produto::class, 'cobertura_produto')
                    ->withPivot('limite_maximo','obrigatoria')
                    ->withTimestamps();
    }

}
