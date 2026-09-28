<?php

namespace App\Models;

use App\Observers\SinistroObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([SinistroObserver::class])]

class Sinistro extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'apolice_id',
        'data_hora_ocorrencia',

        'rua',
        'numero',
        'bairro',
        'complemento',
        'cidade',
        'uf',
        'cep',

        'descricao',
        'coberturas_envolvidas',
        'status',
        'valor_indenizacao',
        'valor_pago',

        'analista_id',
        'aprovado_gestor_id',
        'data_aprovacao_gestor',
    ];

    protected $casts = [
        'data_hora_ocorrencia' => 'datetime',
        'coberturas_envolvidas' => 'array',
    ];

    public const STATUS_ABERTO = 'Aberto';
    public const STATUS_ANALISE = 'Em análise';
    public const STATUS_PERICIA = 'Em perícia';
    public const STATUS_GESTOR = 'Aguardando Gestor';
    public const STATUS_APROVADO = 'Aprovado';
    public const STATUS_PAGO = 'Pago';
    public const STATUS_NEGADO = 'Negado';
    public const STATUS_ENCERRADO = 'Encerrado';

    /**
     * Configura como o log será gerado
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Registra alterações em todas as colunas
            ->logOnlyDirty() // Só gera log das colunas que realmente mudaram
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "O sinistro foi {$eventName}");
    }

    // relacionamentos
    public function apolice()
    {
        return $this->belongsTo(Apolice::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(SinistroMovimentacao::class);
    }

    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function gestorAprovador()
    {
        return $this->belongsTo(User::class, 'aprovado_gestor_id');
    }
}
