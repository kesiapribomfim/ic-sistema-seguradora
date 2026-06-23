<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        ];

        protected $casts = [
            'telefone' => 'string',
            'score' => 'integer',
        ];
        public function seguradoPj()
        {
            return $this->hasOne(SeguradoPj::class); //um segurado tem um tipo pj
        }
        public function seguradoPf()
        {
            return $this->hasOne(SeguradoPf::class); //um seguraod tem um tipo pf
        }

        public function user(){
            return $this->belongsTo(User::class); //Muitos segurados pertencem a um user
        }

        public function cotacoes(){
            return $this->hasMany(Cotacao::class); //
        }
}
