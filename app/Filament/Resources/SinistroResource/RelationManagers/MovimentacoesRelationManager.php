<?php

namespace App\Filament\Resources\SinistroResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MovimentacoesRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentacoes';   
    protected static ?string $title = 'Linha do Tempo e Anexos';
    
    public function isReadOnly(): bool
    {
        $sinistro = $this->getOwnerRecord();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (in_array($sinistro->status, ['Pago', 'Negado', 'Encerrado'])) {
            return true;
        }

        if ($user->hasAnyRole(['Cliente', 'Corretor'])) {
            return true; 
        }

        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('acao_realizada')
                    ->label('Ação Realizada')
                    ->options([
                        'Abertura' => 'Abertura de Sinistro',
                        'Análise' => 'Análise de Documentos',
                        'Perícia' => 'Solicitação de Perícia',
                        'Aprovação' => 'Aprovação de Indenização',
                        'Negação' => 'Negação de Cobertura',
                        'Pagamento' => 'Confirmação de Pagamento',
                        'Encerramento' => 'Encerramento do Sinistro',
                    ])
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('valor_indenizacao')
                    ->label('Valor da Indenização Calculada')
                    ->numeric()
                    ->prefix('R$')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('acao_realizada') === 'Aprovação')
                    ->required(fn (\Filament\Forms\Get $get) => $get('acao_realizada') === 'Aprovação')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição / Parecer')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('anexos')
                    ->label('Anexos Comprobatórios')
                    ->multiple()
                    ->disk('local') 
                    ->directory('sinistros-anexos')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']) 
                    ->maxSize(5120) 
                    ->downloadable() 
                    ->openable()  
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('acao_realizada')
            ->columns([
                Tables\Columns\TextColumn::make('data_hr_movimentacao')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('acao_realizada')
                    ->label('Ação')
                    ->badge(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->description(fn ($record) => $record->user->getRoleNames()->first() ?? 'Usuário'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap() 
                    ->limit(50),
            ])
            ->defaultSort('data_hr_movimentacao', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Movimentação')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['user_id'] = auth()->id();
                        $data['data_hr_movimentacao'] = now();

                        if (($data['acao_realizada'] ?? '') === 'Aprovação') {
                            
                            $sinistro = $livewire->getOwnerRecord();
                            
                            $valor = (float) ($data['valor_indenizacao'] ?? 0);
                            $limite = (float) $sinistro->apolice?->cotacao?->produto?->valor_alcada_aprovacao;
                            
                            $valorFormatado = "R$ " . number_format($valor, 2, ',', '.');
                            $limiteFormatado = "R$ " . number_format($limite, 2, ',', '.');

                            if ($limite > 0 && $valor > $limite && !$sinistro->aprovado_gestor_id && $sinistro->status !== 'Aguardando Gestor') {
                                $data['acao_realizada'] = 'Alçada Excedida';
                                $data['descricao'] = "Indenização calculada ({$valorFormatado}) excede a alçada comercial ({$limiteFormatado}).\n\nParecer do Analista: " . $data['descricao'];
                                
                                $sinistro->update([
                                    'valor_indenizacao' => $valor,
                                    'status' => 'Aguardando Gestor'
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Bloqueio de Alçada')
                                    ->body('O valor excede a sua alçada. O sinistro foi encaminhado para o Gestor de Filial.')
                                    ->warning()
                                    ->send();
                            } else {
                                $data['descricao'] = "Indenização aprovada no valor de {$valorFormatado}.\n\nParecer do Analista: " . $data['descricao'];

                                $sinistro->update([
                                    'valor_indenizacao' => $valor,
                                    'status' => 'Aprovado'
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Sinistro Aprovado')
                                    ->success()
                                    ->send();
                            }
                        }
                        unset($data['valor_indenizacao']);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), 
            ]);
    }
}