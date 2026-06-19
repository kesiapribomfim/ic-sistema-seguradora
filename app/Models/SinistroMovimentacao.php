<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SinistroMovimentacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'sinistro_id',
        'user_id',
        'data_hr_movimentacao',
        'descricao',
        'acao_realizada',
        'anexos',
    ];

    protected $casts = [
        'anexos' => 'array',
        'data_hora_movimentacao' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function sinistro(){
        return $this->belongsTo(Sinistro::class);
    }

}
