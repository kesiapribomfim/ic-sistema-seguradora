<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeguradoPf extends Model
{
    use HasFactory;
    
    protected $table = 'segurado_pf';

    protected $fillable = [
        'cpf',
        'rg',
        'nome',
        'data_nascimento',
        'profissao'
    ];

    public function segurado()
    {
        return $this->belongsTo(Segurado::class);
    }
}
