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

class SinistroResource extends Resource
{
    protected static ?string $model = Sinistro::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Informações Gerais do Evento')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Forms\Components\Select::make('apolice_id')
                            ->relationship(
                                name: 'apolice',
                                titleAttribute: 'numero_apolice',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    -> where ('status', 'Vigente')
                            )
                            ->label('Apólice Vinculada')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\DateTimePicker::make('data_hora_ocorrencia')
                            ->label('Data e Hora da Ocorrência')
                            ->displayFormat('d/m/Y H:i')
                            ->required(),

                        Forms\Components\Placeholder::make('status_visual')
                            ->label('Status da Cotação')
                            ->content(function ($record) {
                                $status = $record ? $record->status : 'Em Elaboração';
                                
                                $cor = match($status) {
                                    'Aberto' => 'warning',
                                    'Em análise', 'Em perícia' => 'info',
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
                            // Libera o campo apenas se o sinistro passar das fases de análise
                            ->disabled(fn (Forms\Get $get) => !in_array($get('status'), ['Aprovado', 'Pago', 'Encerrado']))
                            ->dehydrated(),
                            
                        Forms\Components\TextInput::make('valor_pago')
                            ->label('Valor Efetivamente Pago')
                            ->numeric()
                            ->prefix('R$')
                            // Libera o preenchimento apenas quando o status for marcado como Pago
                            ->disabled(fn (Forms\Get $get) => !in_array($get('status'), ['Pago', 'Encerrado']))
                            ->dehydrated()
                            ->helperText('O valor pode divergir do aprovado em casos de franquia ou rateio.'), // Justificativa de divergência baseada nas regras de negócio[cite: 2].
                    ]),
                    
                Forms\Components\Section::make('Anexos e Timeline')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        // TODO: Resolver isso
                        Forms\Components\Placeholder::make('info_timeline')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="text-sm text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                    <p><strong>Aviso de Arquitetura:</strong> A linha do tempo de movimentações e o upload de arquivos probatórios (fotos, laudos, B.O.) não residem neste formulário base.</p>
                                    <br>
                                    <p>Após salvar este registro, utilize os <em>Relation Managers</em> ou a <em>View Customizada</em> no rodapé da página para gerenciar a evolução cronológica.</p>
                                </div>
                            ')),
                    ]),
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
            // TODO: Implementar filtros de status e datas para facilitar a busca de sinistros específicos.
            // O descritivo menciona o uso de filtros no Table Builder[cite: 2].
            // Aqui você poderá adicionar depois um SelectFilter para 'status' e um Filter para datas.
        ])
        ->actions([
            ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]); 
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        //Permissão super_admin
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Permissão Corretor (restrita a seu id)
        if ($user->hasRole('Corretor')) {
            return $query->whereHas('apolice', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        // TODO: Relacionar Cliente User
        if ($user->hasRole('Cliente')) {
            // Assumindo que você ligou o User ao Segurado em algum lugar, o raciocínio seria:
            // $query->whereHas('apolice.segurado', function($q) use ($user) { $q->where('user_id', $user->id); });
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
