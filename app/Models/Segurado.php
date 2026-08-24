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
            'user_id',
            'corretor_id'
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

        public function corretor()
        {
            return $this->belongsTo(User::class, 'corretor_id');
        }

        public function acessoCliente()
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        public function cotacoes(){
            return $this->hasMany(Cotacao::class); //
        }

        public function apolices()
        {
            return $this->hasMany(Apolice::class);
        }

        public function sinistros()
        {
            return $this->hasManyThrough(Sinistro::class, Apolice::class);
        }
}
