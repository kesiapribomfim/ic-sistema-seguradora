<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Segurado extends Model
{   use HasFactory;

        protected $fillable = [
            'tipo',
            'telefone',
            'email',
            'endereco',
            'bairro',
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
        public function seguradopj()
        {
            return $this->hasOne(SeguradoPj::class);
        }
        public function seguradopf()
        {
            return $this->hasOne(SeguradoPf::class);
        }

        public function user(){
            return $this->belongsTo(User::class);
        }
}
