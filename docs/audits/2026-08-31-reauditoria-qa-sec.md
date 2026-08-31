# Relatório de Auditoria e Melhorias — IC Seguradora

Data: 31/08/2026. Escopo: código presente na branch `main`; a branch de trabalho pretendida é `feature/auditoria-qa-sec`.

## Achados críticos

1. **P0 — Emissão duplicada e baixa financeira sem confirmação.** `CheckoutCotacao` e `ViewCotacao` chamam `EmissaoApoliceService` sem bloqueio de linha, sem garantia única de `cotacao_id` e sem uma transação que valide o estado atual. O serviço cria a primeira parcela como `Paga` no mero aceite. Centralizar a transição num serviço de domínio, fazer `lockForUpdate()`, impor `unique(apolices.cotacao_id)` e criar uma baixa apenas depois da confirmação idempotente do provedor de pagamento.
2. **P0 — Transições de sinistro distribuídas e sem invariante.** `SinistroMovimentacaoObserver` altera estado com base no texto de uma movimentação; `PagamentoObserver` põe o sinistro em `Pago` ao criar qualquer pagamento ligado. Um pagamento deve ser aceito somente para sinistro aprovado, da filial do financeiro, com status Paga e valor validado. Criar um serviço/state machine transacional para as transições, guardar o ator explicitamente e proibir `Pago` de edição.
3. **P1 — Autorização por filial inconsistente.** `PagamentoResource` filtra a consulta, mas `PagamentoPolicy::view` e `update` aceitam o papel sem comparar apólice/cliente/filial. `RelatorioOperacional` aceita um `filial_id` recebido sem revalidá-lo contra as filiais do gestor. Reutilizar um método de escopo por filial na Policy e no relatório; o filtro visual nunca pode ser a única defesa.
4. **P1 — Reset de senha indisponível.** `SeguradoObserver` chama `Password::sendResetLink`, mas não há rotas `password.*` e a view usada pelo controller está removida. Restaurar GET/POST protegidos por throttle, a view e um teste de fluxo do token.
5. **P1 — Usuário inativo pode autenticar no painel.** `User` contém `status`, mas não implementa a barreira de acesso do Filament. Implementar `FilamentUser::canAccessPanel()` com `status === true` e teste de login com usuário inativo.
6. **P1 — Papéis não são multitenant.** Spatie está com `teams=false`, enquanto `filial_user.perfil_acesso` tenta representar papéis por filial. Chamadas globais a `hasRole()` podem conceder permissão fora da filial. Definir uma única fonte de verdade: ativar teams com migração planejada, ou criar uma autorização explícita baseada no pivot e remover decisões globais conflitantes.
7. **P1 — Linha do tempo não é auditada pelo Activitylog.** `Sinistro` importa `LogsActivity`, mas não usa o trait. Além disso, observers assumem `user_id=1` em tarefas sem usuário. Ativar o trait, registrar um ator de sistema identificável e não falsificar autoria humana.

## Performance e confiabilidade

1. **P1 — Rotinas financeiras não são agendadas.** `routes/console.php` não agenda inadimplência ou renovação. O agendador mostra apenas a limpeza do pacote de exportação. Registrar os comandos, aplicar `withoutOverlapping()`/`onOneServer()` e monitorar falhas.
2. **P1 — Inadimplência quebra a atualização de score.** O comando usa `score_risco`; tabela e modelo usam `score`. Corrigir a coluna, processar em lotes e usar locks para evitar notificações/suspensões repetidas.
3. **P1 — Renovação não aproveita índice e pode duplicar.** `DATE(data_fim)` torna o predicado não sargável e a lista de status usa `Em elaboração`, divergente de `Em Elaboração`. Consultar intervalos de data, padronizar enum/status e criar unicidade/idempotência para renovação.
4. **P2 — Dashboard agrega em PHP.** `FaturamentoMensalChart` carrega todas as apólices anuais. Agrupar/somar no PostgreSQL por mês e aplicar cache por filial/ano. Criar índices compostos orientados às consultas após analisar `EXPLAIN` com dados reais.

## Qualidade e manutenção

1. Extrair transições de cotação, sinistro e pagamento de Resources/Observers para serviços testáveis. Observers devem disparar efeitos, não decidir regras financeiras.
2. Remover comparações inválidas como `$user != 'Corretor'`; usar `hasRole('Corretor')`. Elas deixam ações de tabela inacessíveis e escondem defeitos de fluxo.
3. Padronizar constantes/enums para status (`Em Subscrição` versus `Aguardando Subscrição`; `Em Elaboração` versus `Em elaboração`) e criar testes de transição.
4. Validar upload também pelo conteúdo/assinatura, renomear arquivos no servidor, limitar autorização de download e manter o disco privado.

## Validações realizadas

- Leitura das Policies, Resources, models, observers, services, migrations, rotas e painel Filament.
- `php -l` sem erros nos arquivos críticos.
- `php artisan route:list --name=password`: nenhuma rota encontrada.
- `php artisan schedule:list`: somente `filament-excel:prune` agendado.

## Não alterado

Não foram feitas alterações no código de produção nesta auditoria. Há mudanças preexistentes no worktree e elas foram preservadas.
