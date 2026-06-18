<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Filial extends Model
{   
    use HasFactory;

    protected $table = 'filiais';

    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'cep',
    ];

    protected $casts = [
        'telefone' => 'string',
    ];
    
    //Many-to-Many relationship with User model
    public function users()
    {
        return $this->belongsToMany(User::class,'filial_user')
        ->withPivot('perfil_acesso')
        ->withTimestamps();
    }

    public function cotacoes(){
        return $this->hasMany(Cotacao::class); //uma filial tem muitas cotações
    }
}
