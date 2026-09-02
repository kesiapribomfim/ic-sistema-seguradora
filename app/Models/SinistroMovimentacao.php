<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\SinistroMovimentacaoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy([SinistroMovimentacaoObserver::class])]
class SinistroMovimentacao extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'sinistro_movimentacoes';

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
        'data_hr_movimentacao' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Registra alterações em todas as colunas
            ->logOnlyDirty() // Só gera log das colunas que realmente mudaram
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "O sinistro foi {$eventName}");
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function sinistro(){
        return $this->belongsTo(Sinistro::class);
    }

}
