# Reauditoria de segurança, performance e qualidade para a branch de auditoria

- Status: em andamento
- Iniciada em: 2026-08-31 15:21 -03:00

## Objetivo

Reauditoria de segurança, performance e qualidade para a branch de auditoria

## Critérios de aceite

- Relatório de auditoria persistido com achados verificáveis de segurança, desempenho e manutenção.
- Sem alteração do código de produção nem das mudanças preexistentes no worktree.
- Validações de sintaxe, rotas e agendamento registradas.

## Plano e progresso

- [x] Entender o estado relevante
- [x] Executar a auditoria estática e registrar o relatório
- [x] Validar sintaxe, rotas de reset e agendamento
- [x] Registrar handoff e encerrar

## Decisões e descobertas

- A auditoria incidiu sobre o código presente em `main`; a branch alvo informada é `feature/auditoria-qa-sec`, mas ela não foi alterada nesta tarefa.
- A emissão de apólice não possui bloqueio/idempotência e registra a primeira parcela como paga após o aceite; `apolices.cotacao_id` não é único.
- A transição de sinistro é decidida por observers e rótulos textuais. `PagamentoObserver` marca sinistro como pago na criação de qualquer pagamento vinculado e usa o ator fallback `user_id=1`.
- Há divergência de escopo entre `PagamentoResource` e `PagamentoPolicy`; o relatório operacional não revalida a filial recebida para gestor.
- O observer de segurado envia reset de senha, mas a aplicação não tem rotas `password.*` e a view referenciada foi removida.
- Spatie Permission está com `teams=false`, enquanto a regra durável exige papéis por filial; `status` do usuário ainda não é uma barreira de acesso ao painel.
- Inadimplência/renovação não estão no scheduler; inadimplência usa `score_risco`, mas o atributo persistido é `score`; renovação usa uma função sobre a coluna de data e status com grafia divergente.

## Verificação

- `php -l` sem erros em EmissaoApoliceService, CheckoutCotacao, PagamentoObserver, SinistroMovimentacaoObserver, ProcessarInadimplencia e SinistroResource.
- `php artisan route:list --name=password` não encontrou rota de password reset.
- `php artisan schedule:list` listou apenas `filament-excel:prune`; os comandos de inadimplência e renovação não estão agendados.
- Não foram executados testes de integração para não alterar dados do ambiente configurado.

## Handoff

- Relatório entregue em `docs/audits/2026-08-31-reauditoria-qa-sec.md`.
- Próximo passo recomendado: criar/abrir `feature/auditoria-qa-sec` a partir do estado desejado e implementar primeiro a emissão idempotente/baixa confirmada e a máquina de estados de sinistro, com testes de autorização por filial.

- 2026-08-31 15:27: Reauditoria concluída: relatório persistido em docs/audits/2026-08-31-reauditoria-qa-sec.md. Validações: php -l OK; não há rotas password.*; scheduler só contém filament-excel:prune. Nenhum código de produção foi alterado.

## Encerramento

- Encerrada em: 2026-08-31 15:27 -03:00
- Resultado: Reauditoria concluída e registrada; priorizar emissão idempotente, confirmação de pagamento, máquina de estados de sinistro e escopo por filial.
