<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SinistroResource\Pages;
use App\Filament\Resources\SinistroResource\RelationManagers;
use App\Models\Sinistro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

class SinistroResource extends Resource
{
    protected static ?string $model = Sinistro::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = static::getModel()::query();

        if (!$user->hasAnyRole(['super_admin', 'Administrador Geral'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            
            $query->whereHas('apolice', function ($q) use ($filiaisIds) {
                $q->whereIn('filial_id', $filiaisIds);
            });
        }

        if($user->hasRole('Gestor de Filial')) {
            $quantidade = (clone $query)->where('status', 'Aguardando Gestor')->count();
            return $quantidade > 0 ? (string) $quantidade : null;
        }

        if ($user->hasRole('Analista de Sinistros')) {
            $quantidade = (clone $query)->where('status', 'Aberto')->count();
            return $quantidade > 0 ? (string) $quantidade : null;
        }

        if ($user->hasRole('Financeiro')) {
            $aprovado = (clone $query)->where('status', 'Aprovado')->count();
            return $aprovado > 0 ? (string) $aprovado : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = static::getModel()::query();

        if (!$user->hasAnyRole(['super_admin', 'Administrador Geral'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            
            $query->whereHas('apolice', function ($q) use ($filiaisIds) {
                $q->whereIn('filial_id', $filiaisIds);
            });
        }

        $quantidade = 0;
        
        if ($user->hasRole('Analista de Sinistros')) {
            $quantidade = (clone $query)->where('status', 'Aberto')->count();
        } elseif ($user->hasRole('Financeiro')) {
            $quantidade = (clone $query)->where('status', 'Aprovado')->count();
        }

        if ($quantidade > 5) {
            return 'danger'; 
        }

        return 'warning'; 
    }

    public static function form(Form $form): Form
    {
    return $form
        ->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Informações Gerais do Evento')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->disabled(fn ($record) => $record && $record->status !== 'Aberto')
                    ->schema([
                        Forms\Components\Select::make('apolice_id')
                            ->relationship(
                                name: 'apolice',
                                titleAttribute: 'numero_apolice',
                                modifyQueryUsing: function (Builder $query) {
                                    $user = auth()->user();

                                    $query->where('status', 'Vigente');

                                    if ($user->hasRole('Corretor')) {
                                        $query->where('user_id', $user->id);
                                    }

                                    if ($user->hasRole('Cliente')) {
                                        $query->whereHas('segurado', function ($q) use ($user) { 
                                            $q->where('user_id', $user->id); 
                                        });
                                    }

                                    return $query;
                                }
                            )
                            ->label('Apólice Vinculada')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Placeholder::make('segurado_id')
                            ->label('Segurado')
                            ->content(function (Forms\Get $get) {
                                $apoliceId = $get('apolice_id');
                                
                                if (!$apoliceId) return '-';

                                $apolice = \App\Models\Apolice::with(['segurado.seguradoPf', 'segurado.seguradoPj'])->find($apoliceId);
                                $segurado = $apolice?->segurado;

                                if (!$segurado) return '-';
                                
                                return $segurado->tipo === 'PF' 
                                    ? $segurado->seguradoPf?->nome 
                                    : $segurado->seguradoPj?->razao_social;
                            }),
                        Forms\Components\Placeholder::make('produto_id')
                            ->label('Produto')
                            ->content(function (Forms\Get $get) {
                                $apoliceId = $get('apolice_id');
                                
                                if (!$apoliceId) return '-';

                                $apolice = \App\Models\Apolice::with(['cotacao.produto'])->find($apoliceId);
                                $produto = $apolice?->cotacao?->produto;

                                if (!$produto) return '-';
                                
                                return $produto->nome;
                            }),
                        Forms\Components\DateTimePicker::make('data_hora_ocorrencia')
                            ->label('Data e Hora da Ocorrência')
                            ->displayFormat('d/m/Y H:i')
                            ->required()
                            ->minDate(function (\Filament\Forms\Get $get) {
                                $apoliceId = $get('apolice_id');
                                if (!$apoliceId) return null;
                                
                                $apolice = \App\Models\Apolice::find($apoliceId);
                                return $apolice ? $apolice->data_inicio : null;
                            })
                            ->maxDate(function (\Filament\Forms\Get $get) {
                                $apoliceId = $get('apolice_id');
                                if (!$apoliceId) return now(); 
                                
                                $apolice = \App\Models\Apolice::find($apoliceId);
                                if (!$apolice) return now();
                                
                                return $apolice->data_fim->isPast() ? $apolice->data_fim : now();
                            }),
                        Forms\Components\Placeholder::make('status_visual')
                            ->label('Status do Sinistro')
                            ->content(function ($record) {
                                $status = $record ? $record->status : 'Em Elaboração';

                        $cor = match ($status) {
                            'Aberto' => 'warning',
                            'Em análise' => 'info',
                            'Em perícia' => '#735bff',
                            'Aguardando Gestor' => '#f97316',
                            'Aprovado', 'Pago' => 'success',
                            'Negado' => 'danger',
                            'Encerrado' => 'gray',
                            default => 'gray',
                        };

                        return new \Illuminate\Support\HtmlString(
                                    "<span style='color: {$cor}; font-weight: bold;'>{$status}</span>"
                                );
                            }),
                        Forms\Components\Hidden::make('status')
                                ->default('Aberto'), 
                    ])->columns(3),

                Forms\Components\Section::make('Local da Ocorrência')
                    ->icon('heroicon-o-map-pin')
                    ->disabled(fn ($record) => $record && $record->status !== 'Aberto')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('rua')
                                ->label('Rua')
                                ->required(),
                            Forms\Components\TextInput::make('numero')
                                ->label('Numero')
                                ->required(),
                            Forms\Components\TextInput::make('bairro') ->required(),
                            Forms\Components\TextInput::make('complemento'),
                            Forms\Components\TextInput::make('cidade') -> required(),
                            Forms\Components\TextInput::make('uf')
                                ->label('UF')
                                ->required()
                                ->maxLength(2)
                                ->extraAttributes(['style'=>'text-transform: uppercase']),
                            Forms\Components\TextInput::make('cep')
                                ->label('CEP')
                                ->required()
                                ->mask('99.999-999')
                                ->stripCharacters(['.', '-']),
                        ]),
                    ]),

                Forms\Components\Section::make('Detalhamento do Sinistro')
                    ->icon('heroicon-o-document-text')
                    ->disabled(fn ($record) => $record && $record->status !== 'Aberto')
                    ->schema([
                        Forms\Components\CheckboxList::make('coberturas_envolvidas')
                            ->label('Coberturas Acionadas')
                            ->options(function (Forms\Get $get) {
                                $apoliceId = $get('apolice_id');
                                if (!$apoliceId) {
                                    return []; // Retorna vazio se não tiver apólice selecionada
                                }

                                $apolice = \App\Models\Apolice::find($apoliceId);
                                if (!$apolice || empty($apolice->snapshot['coberturas'])) {
                                    return [];
                                }

                                // Mapeia o array de coberturas do snapshot
                                $opcoes = [];
                                foreach ($apolice->snapshot['coberturas'] as $cobertura) {
                                    // AQUI ESTAVA O SEGREDO: 'nome_cobertura'
                                    $nomeCobertura = $cobertura['nome_cobertura'] ?? 'Cobertura Desconhecida';
                                    $opcoes[$nomeCobertura] = $nomeCobertura; 
                                }
                                return $opcoes;
                            })
                            ->required()
                            ->columns(2)
                            ->helperText('Selecione as coberturas afetadas por este evento.'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição do Evento')
                            ->placeholder('Relate os detalhes do ocorrido...')
                            ->columnSpanFull()
                            ->rows(4)
                            ->required(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Controle Financeiro')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                    Forms\Components\TextInput::make('valor_indenizacao')
                        ->label('Indenização Aprovada')
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('valor_pago')
                        ->label('Valor Efetivamente Pago')
                        ->numeric()
                        ->prefix('R$')
                        ->disabled(fn(Forms\Get $get) => !(auth()->user()->hasRole('Financeiro') && $get('status') === 'Aprovado'))
                        ->dehydrated(),
                    ]),
                    
                Forms\Components\FileUpload::make('anexos_temporarios')
                    ->visible(fn () => auth()->user()->hasRole('Cliente'))
                    ->label('Evidências Iniciais (B.O., Fotos, CNH)')
                    ->multiple()
                    ->disk('local') 
                    ->directory('sinistros-anexos')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']) 
                    ->maxSize(5120) 
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ])->columnSpan(['lg' => 1]), // Ocupa 1/3 da tela
        ])
        ->columns(3);
    }

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('Protocolo')
                ->sortable()
                ->searchable()
                ->fontFamily('mono')
                ->weight('bold')
                ->prefix('SIN-'), // Adiciona um prefixo visual sem alterar o banco

            Tables\Columns\TextColumn::make('apolice.numero_apolice')
                ->label('Apólice Vinculada')
                ->searchable()
                ->sortable()
                ->color('info')
                ->url(fn (Model $record) => $record->apolice_id ? \App\Filament\Resources\ApoliceResource::getUrl('view', ['record' => $record->apolice_id]) : null)
                ->openUrlInNewTab(),

            // Buscando o cliente dinamicamente através do relacionamento da Apólice
            Tables\Columns\TextColumn::make('identificacao_segurado')
                ->label('Segurado')
                ->state(function (Model $record) {
                    $segurado = $record->apolice?->segurado;
                    if (!$segurado) return '-';
                    
                    return $segurado->tipo === 'PF' 
                        ? $segurado->seguradoPf?->nome 
                        : $segurado->seguradoPj?->razao_social;
                })
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('data_hora_ocorrencia')
                ->label('Data do Evento')
                ->dateTime('d/m/Y H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('valor_indenizacao')
                ->label('Indenização Aprovada')
                ->money('BRL')
                ->placeholder('-')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Aberto' => 'warning',
                    'Em análise', 'Em perícia' => 'info',
                    'Aprovado', 'Pago' => 'success',
                    'Negado' => 'danger',
                    'Encerrado' => 'gray',
                    default => 'gray',
                }),
        ])
        ->recordUrl(null)
        ->recordAction(Tables\Actions\ViewAction::class)
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->label('Status do Sinistro')
                ->options([
                    'Aberto' => 'Aberto',
                    'Em análise'=> 'Em análise',
                    'Em perícia' => 'Em perícia',
                    'Aprovado' => 'Aprovado',
                    'Pago' => 'Pago',
                    'Negado' => 'Negado',
                    'Encerrado' => 'Encerrado',
                ])
        ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),

                    // 1. Ação existente de Assumir
                    Tables\Actions\Action::make('assumir')
                        ->label('Assumir Sinistro')
                        ->icon('heroicon-o-hand-raised')
                        ->color('success')
                        ->visible(
                            fn(Model $record) =>
                            auth()->user()->hasAnyRole(['Analista de Sinistros', 'Gestor de Filial']) &&
                                is_null($record->analista_id)
                        )
                        ->action(function (Model $record) {
                            $record->update([
                                'analista_id' => auth()->id(),
                                'status' => 'Em análise',
                            ]);

                            $record->movimentacoes()->create([
                                'user_id' => auth()->id(),
                                'data_hr_movimentacao' => now(),
                                'acao_realizada' => 'Análise',
                                'descricao' => 'Sinistro assumido para análise e regulação.',
                            ]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Assumir Sinistro')
                        ->modalDescription('Você será designado como o analista responsável por este sinistro. Deseja continuar?'),

                    Tables\Actions\Action::make('aprovar')
                        ->label('Aprovar Sinistro')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(
                            fn(Model $record) =>
                            in_array($record->status, ['Em análise', 'Em perícia', 'Aguardando Documentação']) &&
                            auth()->user()->hasAnyRole(['Analista de Sinistros', 'Gestor de Filial'])
                        )
                        ->form([
                            Forms\Components\TextInput::make('valor_indenizacao')
                                ->label('Valor da Indenização Calculada')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                        ])
                        ->action(function (array $data, Model $record) {
                            $valor = (float) $data['valor_indenizacao'];
                            $limite = $record->apolice?->cotacao?->produto?->valor_alcada_aprovacao;

                            if ($limite && $valor > $limite && !$record->aprovado_gestor_id) {
                                $record->update([
                                    'valor_indenizacao' => $valor,
                                    'status' => 'Aguardando Gestor'
                                ]);

                                $record->movimentacoes()->create([
                                    'user_id' => auth()->id(),
                                    'data_hr_movimentacao' => now(),
                                    'acao_realizada' => 'Alçada Excedida',
                                    'descricao' => "Indenização de R$ " . number_format($valor, 2, ',', '.') . " excede o limite do produto (R$ " . number_format($limite, 2, ',', '.') . "). Enviado para dupla aprovação.",
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Bloqueio de Alçada')
                                    ->body('O valor excede a sua alçada. O sinistro foi encaminhado para o Gestor de Filial.')
                                    ->warning()
                                    ->send();
                            } else {
                                $record->update([
                                    'valor_indenizacao' => $valor,
                                    'status' => 'Aprovado'
                                ]);

                                $record->movimentacoes()->create([
                                    'user_id' => auth()->id(),
                                    'data_hr_movimentacao' => now(),
                                    'acao_realizada' => 'Aprovação',
                                    'descricao' => "Sinistro regulado e aprovado no valor de R$ " . number_format($valor, 2, ',', '.') . ".",
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Sinistro Aprovado')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('aprovar_gestor')
                        ->label('Autorizar Exceção (Gestor)')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->visible(
                            fn(Model $record) =>
                            $record->status === 'Aguardando Gestor' &&
                                auth()->user()->hasAnyRole(['Gestor de Filial', 'Administrador Geral'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Autorizar Indenização Acima da Alçada')
                        ->modalDescription(fn(Model $record) => new HtmlString("Você está prestes a liberar o pagamento de <strong>R$ " . number_format($record->valor_indenizacao, 2, ',', '.') . "</strong>. Deseja prosseguir?"))
                        ->action(function (Model $record) {
                            $record->update([
                                'aprovado_gestor_id' => auth()->id(),
                                'data_aprovacao_gestor' => now(),
                                'status' => 'Aprovado'
                            ]);

                            $record->movimentacoes()->create([
                                'user_id' => auth()->id(),
                                'data_hr_movimentacao' => now(),
                                'acao_realizada' => 'Aprovação de Gestor',
                                'descricao' => "Dupla aprovação realizada. Indenização liberada.",
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Indenização Liberada')
                                ->success()
                                ->send();
                        }),
                ])
            ])        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]); 
    }
    

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
                ->with(['apolice.segurado.seguradoPf', 'apolice.segurado.seguradoPj']);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        //Permissão super_admin
        if ($user->hasAnyRole(['super_admin', 'Administrador Geral'])) {
            return $query;
        }

        // Permissão Corretor (restrita a seu id)
        if ($user->hasRole('Corretor')) {
            return $query->whereHas('apolice', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        if ($user->hasAnyRole(['Cliente', 'Corretor'])) {
            return $query->whereHas('apolice.segurado', function($q) use ($user) { $q->where('user_id', $user->id); });
        }

        // Analista, Gestor e Financeiro ligado a filial
        $filiaisIds = $user->filiais()->pluck('filiais.id');
        
        return $query->whereHas('apolice', function (Builder $query) use ($filiaisIds) {
            $query->whereIn('filial_id', $filiaisIds);
        });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovimentacoesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSinistros::route('/'),
            'create' => Pages\CreateSinistro::route('/create'),
            'view' => Pages\ViewSinistro::route('/{record}/view'),
            'edit' => Pages\EditSinistro::route('/{record}/edit'),
        ];
    }
}
