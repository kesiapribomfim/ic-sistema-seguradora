# Detalhar solução técnica dos achados P0 de emissão de apólice e transição de sinistro

- Status: em andamento
- Iniciada em: 2026-08-31 17:18 -03:00

## Objetivo

Detalhar solução técnica dos achados P0 de emissão de apólice e transição de sinistro

## Critérios de aceite

- A preencher antes da implementação quando necessário.

## Plano e progresso

- [ ] Entender o estado relevante
- [ ] Executar o trabalho
- [ ] Validar o resultado
- [ ] Registrar handoff e encerrar

## Decisões e descobertas

- Tarefa criada.

## Verificação

- Ainda não executada.

## Handoff

- Próximo passo: entender o estado relevante.

- 2026-08-31 17:19: Detalhamento P0 entregue: emissão deve separar aceite, intenção de pagamento, webhook autenticado e emissão idempotente com lock e unicidade de cotacao_id; sinistro deve usar máquina de estados/serviços transacionais, removendo transição por texto em observer e validando filial, alçada, ator e pagamento confirmado.

## Encerramento

- Encerrada em: 2026-08-31 17:19 -03:00
- Resultado: Detalhamento dos P0 concluído; próximo passo é implementar primeiro o fluxo idempotente de emissão e os seus testes de concorrência.
