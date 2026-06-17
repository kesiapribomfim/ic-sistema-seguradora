<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cotacao extends Model
{
    use HasFactory;

    protected $table = 'cotacoes';


    protected $fillable = [
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

    //fk
    public function segurado(){
        return $this->belongsTo(Segurado::class);

    }

    public function produto(){
        return $this->belongsTo(Produto::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function filial(){
        return $this->belongsTo(Filial::class);
    }

    public function apolice(){
        return $this->hasOne(Apolice::class);
    }
}
