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
        return $this->belongsTo(Segurado::class); //muitos para um segurado
    }

    public function produto(){
        return $this->belongsTo(Produto::class); //muitos para um produto
    }

    public function user(){
        return $this->belongsTo(User::class);//muitos para um usuario
    }

    public function filial(){
        return $this->belongsTo(Filial::class); //muitos para uma filial
    }

    public function apolices(){
        return $this->hasOne(Apolice::class); //uma para uma apolice
    }
}
