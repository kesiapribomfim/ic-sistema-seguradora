<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    protected $fillable = [
        'nome',
        'cpf',
        'data_nascimento',
        'parentesco',
    ];

    public function apolices()
    {
        return $this->belongsToMany(Apolice::class, 'apolice_beneficiario')
            ->withPivot('percentual_rateio', 'parentesco')
            ->withTimestamps();
    }
}
