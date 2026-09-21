<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguradoPf extends Model
{
    use HasFactory;

    protected $table = 'segurado_pf';

    protected $fillable = [
        'segurado_id',
        'cpf',
        'rg',
        'nome',
        'data_nascimento',
        'profissao',
    ];

    public function segurado()
    {
        return $this->belongsTo(Segurado::class);
    }
}
