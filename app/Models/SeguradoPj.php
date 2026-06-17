<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeguradoPj extends Model
{
    use HasFactory;

    protected $table = 'segurado_pj';


    protected $fillable = [
        'cnpj',
        'razao_social',
        'inscricao_estadual'
       
    ];

    protected $casts=[
        'telefone'=>'string',
    ];

    public function segurado()
    {
        return $this->belongsTo(Segurado::class);
    }
}
