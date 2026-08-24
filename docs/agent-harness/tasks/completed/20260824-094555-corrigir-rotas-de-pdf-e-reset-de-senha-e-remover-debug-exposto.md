# Corrigir rotas de PDF e reset de senha, e remover debug exposto

- Status: em andamento
- Iniciada em: 2026-08-24 09:45 -03:00

## Objetivo

Corrigir rotas de PDF e reset de senha, e remover debug exposto

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

- 2026-08-24 09:48: Branch feature/auditoria-qa-sec criada. Implementados: PDF protegido por auth+Policy em /apolices/{apolice}/pdf; rota pública teste-pdf removida; reset passou para formulário POST com Password::reset; dd() substituído por report() e mensagem genérica. Validação: php -l, route:list e busca de dd passaram.

## Encerramento

- Encerrada em: 2026-08-24 09:48 -03:00
- Resultado: Item 2 de segurança concluído e validado estaticamente. Próximo passo opcional: testar no navegador os cenários de PDF, reset e erro controlado conforme instruções de handoff.
