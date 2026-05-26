# EAP do Projeto SisViaturas

## 1. Visão Geral

Esta Estrutura Analítica do Projeto organiza o trabalho técnico e funcional do plugin **SisViaturas / vehiclescheduler** para GLPI 11.

A EAP considera:

- evolução do plugin existente;
- separação entre backend/domínio, front/renderização, AJAX, CSS e JS;
- internacionalização como frente própria;
- execução obrigatória da internacionalização e do refactor estrutural antes da implementação do módulo de manutenção;
- eliminação do diretório legado de classes e consolidação de implementação em `src/`;
- carregamento das classes GLPI compatibility via Composer classmap em `src/Legacy/`;
- módulo de manutenção com MVP operacional;
- manutenção da compatibilidade com GLPI 11;
- uso de inglês como idioma-base técnico e `msgid`.

---

## 2. EAP Macro

```text
1. Governança e Arquitetura
2. Internacionalização do Plugin
3. Base Técnica e Qualidade
4. Refactor Estrutural de Telas e Pastas
5. Revisão e Consolidação de CSS
6. Funcionalidades Existentes
7. Módulo de Manutenção - MVP
8. Relatórios, Indicadores e Dashboard
9. Segurança, Permissões e Auditoria
10. Documentação e Entrega
11. Evoluções Futuras
```

---

## 3. Pacotes de Trabalho

> Ordem obrigatória de execução: concluir a frente de **Internacionalização do Plugin** e o **Refactor Estrutural de Telas e Pastas** antes de iniciar a implementação funcional do **Módulo de Manutenção - MVP**. A manutenção pode ser analisada e documentada em paralelo, mas sua codificação deve partir da base i18n e da estrutura técnica já consolidadas.

> Estado atual da execução técnica: a consolidação estrutural das classes em `src/` foi executada, com classes GLPI compatibility movidas para `src/Legacy/`, helpers bootstrap movidos para `src/Bootstrap/`, autoload Composer ajustado e validação de instalação/ativação realizada. A frente de internacionalização funcional foi consolidada com catálogo gettext regenerado, locales preenchidos nos quatro idiomas e seletor de idioma estabilizado. A frente de validação de inputs foi reforçada com `src/Support/InputNormalizer.php` e uso via `PluginVehicleschedulerInput`. O **Marco 3 - Revisão Visual e CSS** foi concluído, com inventário, remoção de órfãos e componentes compartilhados estabilizados; a próxima frente é o **Marco 4 - Preparação Técnica da Manutenção**.

### Controle Executivo da EAP

| Marco | Frente | Status | Observação |
| --- | --- | --- | --- |
| 1 | Internacionalização e Fundação Técnica | Concluído | Base i18n, locales, seletor de idioma, mensagens e padrão de `msgid` em inglês estabilizados. |
| 2 | Refactor Estrutural | Concluído | Classes compatibility em `src/Legacy/`, helpers em `src/Bootstrap/`, autoload e estrutura base consolidados. |
| 3 | Revisão Visual e CSS | Concluído | Inventário CSS revisado, órfãos removidos, componentes compartilhados criados e carregamento central validado. |
| 4 | Preparação Técnica da Manutenção | Planejado | Próxima frente após conclusão da revisão visual/CSS. |
| 5 | Oficina MVP | Planejado | Depende do Marco 4. |
| 6 | OS MVP | Planejado | Depende do Marco 4 e da base de oficina. |
| 7 | Integrações Operacionais | Planejado | Depende do fluxo de OS MVP. |
| 8 | Indicadores | Planejado | Depende de dados operacionais da manutenção. |

### 1. Governança e Arquitetura

#### 1.1 Regras do Projeto

- Manter `AGENTS.md` como fonte normativa.
- Manter separação de responsabilidades por camada.
- Não recriar diretório legado removido.
- Priorizar novas classes em `src/` com namespace PSR-4.

#### 1.2 Modelo Arquitetural

- Definir fronteiras entre domínio, renderização e endpoints.
- Definir serviços de domínio para regras de negócio.
- Definir helpers compartilhados apenas quando houver uso real.
- Preservar entry points finos em `front/` e `ajax/`.

#### 1.3 Padrões Técnicos

- PHP PSR-12.
- Comentários técnicos em inglês.
- Nomes técnicos em inglês.
- Campos internos e constantes em inglês.
- Labels user-facing via locales.

---

### 2. Internacionalização do Plugin

#### 2.1 Padrão i18n

- Adotar inglês como idioma-base técnico.
- Usar inglês nos `msgid`.
- Usar domínio `vehiclescheduler`.
- Manter português, espanhol e francês nos arquivos de locale.

#### 2.2 Catálogo de Traduções

- Revisar `locales/vehiclescheduler.pot`.
- Atualizar `pt_BR`, `en_GB`, `es_ES` e `fr_FR`.
- Corrigir inconsistências de encoding em textos existentes.
- Padronizar mensagens de validação, sucesso, erro e aviso.

#### 2.3 Internacionalização do Código Existente

- Priorizar menus e navegação.
- Priorizar botões e ações comuns.
- Priorizar mensagens flash.
- Priorizar status e labels de entidades.
- Priorizar telas usadas na gestão operacional.

#### 2.4 Internacionalização do Módulo de Manutenção

- Criar chaves para Ordem de Serviço.
- Criar chaves para Oficina.
- Criar chaves para especialidades.
- Criar chaves para status da OS.
- Criar chaves para diagnóstico, orçamento, aprovação, execução e liberação.
- Criar helpers de labels traduzíveis para enums/status.

#### 2.5 Critérios de Aceite de i18n

- Nenhum texto novo user-facing deve entrar hardcoded.
- Labels novas devem ter tradução nos quatro idiomas.
- Status/enums devem expor labels traduzíveis.
- O plugin deve continuar funcional mesmo com locale incompleto.

---

### 3. Base Técnica e Qualidade

#### 3.1 Estrutura de Código

- Revisar pontos com HTML em classes backend.
- Isolar regras de negócio em serviços.
- Reduzir duplicação entre `front/*.php`.
- Manter compatibilidade com entry points GLPI.

#### 3.2 Banco de Dados e Migrações

- Manter schema em `hook.php` idempotente.
- Usar helpers do projeto para colunas e índices.
- Não usar `$DB->indexExists()`.
- Evitar SQL cru, exceto quando necessário via `$DB->doQuery()`.

#### 3.3 Qualidade e Validação

- Validar sintaxe PHP dos arquivos alterados.
- Validar JavaScript com `node --check` quando aplicável.
- Revisar permissões antes de persistir dados.
- Centralizar validação de inputs sensíveis.

#### 3.4 UI Base

- Manter componentes visuais consistentes.
- Evoluir flash messages como componente reutilizável.
- Garantir compatibilidade com tema claro e escuro.
- Evitar textos e controles sobrepostos.

---

### 4. Refactor Estrutural de Telas e Pastas

#### 4.1 Inventário do Legado

- Mapear classes GLPI compatibility em `src/Legacy/`.
- Identificar quais classes permanecem com nomes `PluginVehiclescheduler...` por compatibilidade GLPI.
- Identificar lógica de domínio que pode migrar para `src/`.
- Identificar telas em `front/` com regra de negócio indevida.
- Identificar duplicações entre telas, renderizações e helpers.

#### 4.2 Consolidação em `src/`

- Criar classes novas em `src/` com namespace `GlpiPlugin\Vehiclescheduler`.
- Migrar regras de negócio para serviços ou classes de domínio em `src/`.
- Manter classes globais GLPI apenas em `src/Legacy/`, carregadas por Composer classmap.
- Evitar criar novas classes legacy-style sem motivo de compatibilidade.
- Preservar nomes e contratos públicos usados pelo GLPI durante a transição.
- Manter helpers bootstrap compartilhados em `src/Bootstrap/`.
- Garantir que entry points não façam include manual de arquivos de classes.
- Regenerar autoload Composer após qualquer movimentação estrutural.

#### 4.3 Organização de Telas

- Manter `front/*.php` como entry points finos.
- Separar renderização, orquestração e regra de negócio.
- Reduzir duplicação entre telas de listagem, formulário e visualização.
- Padronizar carregamento de CSS/JS por página.
- Garantir que telas novas da manutenção já nasçam no padrão refatorado.

#### 4.4 Helpers e Componentes Compartilhados

- Consolidar helpers realmente reutilizáveis.
- Evitar helpers globais para regras específicas de uma tela.
- Padronizar componentes de alerta, botões, filtros, tabelas, formulários e cabeçalhos.
- Garantir que novos componentes usem labels traduzíveis.

#### 4.5 Critérios de Aceite do Refactor

- Novas regras de domínio devem residir em `src/`.
- `front/` e `ajax/` devem permanecer finos.
- Nenhuma nova funcionalidade de manutenção deve depender de regra de negócio hardcoded na tela.
- Compatibilidade com telas existentes deve ser preservada.
- Sintaxe PHP e comportamento principal devem ser validados após cada migração.
- Diretório legado removido não deve existir no plugin.
- Classes globais `PluginVehiclescheduler...` devem ser carregadas por Composer classmap a partir de `src/Legacy/`.
- Helpers compartilhados devem ser carregados a partir de `src/Bootstrap/`.
- Referências operacionais ao diretório legado removido devem ser inexistentes no código do plugin.
- Instalação e ativação do plugin devem continuar funcionando no GLPI.

---

### 5. Revisão e Consolidação de CSS

**Status:** concluído.

**Objetivo entregue:** mapear a superfície atual de CSS, reduzir duplicidades seguras, remover arquivos órfãos e consolidar componentes compartilhados sem apagar estilos específicos que ainda sustentam telas existentes.

**Inventário final:** `public/css/` possui 35 arquivos CSS, sendo 4 na raiz, 4 em `public/css/core/` e 27 em `public/css/pages/`. O carregamento padrão passa por `public/css/app.css`, que expande imports locais via `plugin_vehiclescheduler_load_css()`. Existem wrappers de compatibilidade em `public/css/pages/management.css`, `public/css/pages/schedule.css` e `public/css/pages/schedule-form.css`, apontando para arquivos raiz antigos.

**Consolidação realizada:** `public/css/core/components.css` concentra componentes compactos compartilhados como pílulas de status/CNH, ações de linha e painel de tabela. `public/css/app.css` passou a carregar explicitamente os componentes compartilhados e `public/css/pages/admin-dashboard.css`. Arquivos CSS sem referência operacional foram removidos: `public/css/core/vehicle-grid.css`, `public/css/pages/driver-list.css` e `public/css/pages/vehicle-list.css`.

**Validação técnica:** imports CSS e balanceamento de chaves foram verificados por script estático em todos os arquivos CSS. Referências operacionais aos arquivos removidos foram checadas no código do plugin, excluindo histórico/changelog. A validação visual runtime depende de sessão GLPI ativa, mas a revisão preservou seletores específicos das telas em uso, incluindo `vehicle-grid.css`, que permanece carregado por `front/vehicle.php`.

#### 5.1 Mapeamento dos Estilos

- [x] Mapear os arquivos CSS existentes em `public/css/pages/`.
- [x] Identificar arquivos por tela, componente, grid, formulário e visualização.
- [x] Levantar dependências entre `public/css/app.css`, `public/css/core/` e `public/css/pages/`.
- [x] Registrar padrões visuais recorrentes antes de qualquer consolidação.

#### 5.2 Análise de Duplicidade

- [x] Identificar estilos duplicados ou muito semelhantes.
- [x] Comparar padrões de layout, grid, formulário, botões, tabelas, cards e views.
- [x] Separar estilos realmente específicos de tela de estilos reutilizáveis.
- [x] Identificar CSS que existe apenas por crescimento incremental de telas.

#### 5.3 Consolidação de Componentes Visuais

- [x] Consolidar padrões comuns em arquivos compartilhados.
- [x] Reorganizar classes utilitárias e componentes visuais reutilizáveis quando necessário.
- [x] Reduzir CSS específico por componente quando houver reaproveitamento claro.
- [x] Evitar que novas telas gerem CSS próprio sem necessidade real.

#### 5.4 Compatibilidade Visual

- [x] Garantir compatibilidade com tema claro e escuro.
- [x] Preservar comportamento responsivo das páginas existentes.
- [x] Evitar regressões em grids, formulários, listagens, cards e telas de visualização.
- [x] Validar que textos, botões e elementos interativos não fiquem sobrepostos.

#### 5.5 Critérios de Aceite de CSS

- [x] CSS mais enxuto e com menor duplicidade.
- [x] Padrões visuais consistentes entre grid, form, view e demais componentes.
- [x] Reaproveitamento claro entre telas.
- [x] CSS específico restrito a diferenças reais de cada página.
- [x] Principais páginas validadas estruturalmente após a revisão; inspeção visual runtime deve acompanhar a próxima sessão GLPI autenticada.

---

### 6. Funcionalidades Existentes

#### 6.1 Viaturas

- Preservar cadastro de viaturas.
- Usar viatura como referência para reservas, incidentes e manutenção.
- Preparar uso futuro de status operacional e odômetro.

#### 6.2 Motoristas

- Preservar cadastro e regras de CNH.
- Manter integração com reservas e alocação operacional.

#### 6.3 Reservas

- Preservar solicitação de reserva.
- Preservar aprovação/rejeição.
- Preservar validação de conflitos.
- Preservar atribuição de veículo/motorista.

#### 6.4 Incidentes

- Preservar registro de incidentes.
- Manter indicador de necessidade de manutenção.
- Preparar geração de OS corretiva a partir de incidente.

#### 6.5 Checklists

- Preservar estrutura existente.
- Preparar integração com OS por não conformidade.
- Preparar uso de odômetro e bloqueio operacional.

---

### 7. Módulo de Manutenção - MVP

#### 7.1 Escopo do MVP

- Cadastro simples de oficinas próprias e credenciadas.
- Especialidades de oficina como filtro auxiliar.
- Abertura de Ordem de Serviço manual.
- OS corretiva a partir de incidente ou checklist não conforme.
- OS preventiva simples por data e quilometragem.
- Diagnóstico.
- Orçamento simples.
- Aprovação simples.
- Execução.
- Conclusão.
- Liberação da viatura.
- Registro de custo estimado e custo final.

#### 7.2 Fora do MVP

- Controle de contratos de oficinas.
- Controle de saldo contratual.
- Abatimento automático por OS.
- Gestor do contrato.
- Fiscal administrativo.
- Fiscal técnico.
- Portal externo de oficina.
- Ranking de oficinas.
- Controle avançado de estoque de peças.
- Integração financeira completa.

#### 7.3 Oficina

- Criar entidade de oficina.
- Campos mínimos:
  - nome;
  - tipo;
  - documento;
  - telefone;
  - e-mail;
  - cidade;
  - UF;
  - status;
  - especialidades;
  - observações.
- Permitir ativar/inativar oficina.
- Permitir filtrar oficinas ativas por especialidade.

#### 7.4 Ordem de Serviço

- Criar ou evoluir entidade central de OS.
- Vincular OS à viatura existente.
- Vincular OS à oficina responsável.
- Registrar tipo de manutenção.
- Registrar origem.
- Registrar prioridade.
- Registrar status.
- Registrar diagnóstico.
- Registrar orçamento/custo estimado.
- Registrar custo final.
- Registrar odômetro de abertura e conclusão.

#### 7.5 Fluxo Essencial da OS

```text
abertura
→ análise
→ vinculação da oficina
→ diagnóstico/orçamento
→ aprovação, se necessário
→ execução
→ conclusão
→ liberação da viatura
```

#### 7.6 Serviços e Peças

- Registrar serviços executados de forma simples.
- Registrar peças utilizadas de forma simples.
- Não implementar estoque avançado no MVP.

#### 7.7 Integração com Viatura

- Atualizar status operacional quando aplicável.
- Bloquear viatura em OS crítica ou checklist reprovado.
- Liberar viatura após conclusão e validação.
- Atualizar odômetro quando o valor informado for maior que o atual.

#### 7.8 Histórico

- Registrar histórico de OS concluídas.
- Registrar custos estimado/final.
- Registrar oficina responsável.
- Registrar odômetro.
- Registrar eventos críticos.

---

### 8. Relatórios, Indicadores e Dashboard

#### 8.1 Dashboard Operacional

- OS abertas.
- OS atrasadas.
- Viaturas em manutenção.
- Preventivas próximas.
- Preventivas vencidas.
- Custo de manutenção no mês.

#### 8.2 Relatórios

- Custo por viatura.
- Custo por oficina.
- Custo por tipo de manutenção.
- OS por período.
- Preventivas vencidas.
- Tempo de parada.

#### 8.3 Alertas

- Preventiva próxima.
- Preventiva vencida.
- OS atrasada.
- Aprovação pendente.
- Checklist reprovado.
- Viatura bloqueada.

---

### 9. Segurança, Permissões e Auditoria

#### 9.1 Permissões

- Reutilizar permissões existentes quando possível.
- Separar visualização, gestão e aprovação.
- Backend deve impor ACL.
- Frontend pode apenas ocultar controles.

#### 9.2 Auditoria

- Registrar abertura de OS.
- Registrar alteração de status.
- Registrar alteração de prioridade.
- Registrar troca de oficina.
- Registrar aprovação/reprovação.
- Registrar alteração de custo.
- Registrar bloqueio/liberação de viatura.
- Registrar conclusão/cancelamento.

#### 9.3 Rastreabilidade

- Manter vínculo entre viatura, OS, oficina, checklist, orçamento e histórico.
- Evitar exclusão física de registros críticos.
- Preferir cancelamento/inativação quando necessário.

---

### 10. Documentação e Entrega

#### 10.1 Documentação Técnica

- Manter `AGENTS.md`.
- Manter especificação de manutenção em `docs/`.
- Documentar decisões de escopo.
- Documentar migrações relevantes.

#### 10.2 Documentação de Uso

- Atualizar READMEs da raiz.
- Atualizar guias de instalação quando necessário.
- Documentar permissões e perfis.
- Documentar fluxo operacional do MVP.

#### 10.3 Entrega

- Validar instalação/atualização.
- Validar permissões.
- Validar telas principais.
- Validar locale em português, inglês, espanhol e francês.

---

### 11. Evoluções Futuras

#### 11.1 Contratos

- Contrato vinculado à oficina.
- Valor global contratado.
- Saldo disponível.
- Consumo por OS.
- Gestor do contrato.
- Fiscal administrativo.
- Fiscal técnico.

#### 11.2 Oficina Avançada

- Ranking de oficinas.
- Índice de retrabalho.
- Prazo médio de atendimento.
- Contratos e garantias.

#### 11.3 Manutenção Avançada

- Manutenção preditiva.
- Integração com telemetria.
- Integração com estoque.
- Integração financeira.
- Portal externo da oficina.

---

## 4. Execução da EAP - Módulo de Manutenção de Viaturas

> Atenção: a execução funcional da manutenção deve começar somente após a frente de internacionalização, o refactor estrutural de telas/pastas e a revisão base de CSS estarem concluídos ou suficientemente estabilizados. Esta seção orienta o módulo de manutenção, mas não altera a prioridade combinada: **i18n primeiro, refactor estrutural depois, manutenção por último**.

### 4.1 Base da Execução

A execução do módulo de manutenção parte da premissa de controlar o processo essencial de manutenção, sem transformar o plugin em um sistema completo de gestão de contratos, estoque, financeiro ou portal de oficinas.

A especificação funcional ampla permanece como referência, mas o MVP deve reduzir o escopo para um fluxo operacional controlável, centrado na Ordem de Serviço.

O padrão técnico adotado para a execução é:

- classes próprias do plugin em `src/`;
- telas e entry points em `front/`;
- chamadas assíncronas em `ajax/`;
- estilos em `public/css/`;
- comportamento client-side em `public/js/`;
- traduções via `gettext` em `locales/`;
- inglês como idioma-base técnico para classes, métodos, campos internos, constantes e `msgid`.

### 4.2 Decisões Assumidas

#### OS como entidade central

A manutenção será tratada conceitualmente como **Ordem de Serviço de Manutenção**.

O registro atual de manutenção poderá ser evoluído para representar a OS simplificada, evitando criar uma estrutura paralela desnecessária no MVP.

#### MVP reduzido

Entram no MVP:

- OS de manutenção;
- oficina responsável;
- serviços/especialidades da oficina;
- diagnóstico;
- custo estimado;
- aprovação simples;
- execução;
- custo final;
- bloqueio/liberação da viatura;
- histórico básico por OS;
- dashboard mínimo;
- auditoria essencial;
- internacionalização;
- revisão de CSS.

Ficam fora do MVP:

- contratos;
- controle de saldo contratual;
- estoque de peças;
- catálogo completo de serviços;
- portal de oficina;
- ranking de oficinas;
- SLA;
- integração financeira;
- manutenção preditiva;
- telemetria.

### 4.3 Frente A - Análise e Redução de Escopo

#### Objetivo

Revisar o processo completo e reduzir o escopo ao mínimo necessário para controle operacional da manutenção.

#### Atividades

- Revisar fluxo atual de manutenção.
- Identificar o que já existe no plugin.
- Separar funcionalidades obrigatórias, úteis e futuras.
- Reduzir status da OS.
- Reduzir entidades novas.
- Definir fluxo mínimo de manutenção.
- Registrar pontos que ficam para versões futuras.

#### Entregáveis

- Matriz MVP x Futuro.
- Fluxo reduzido da OS.
- Lista de decisões assumidas.
- Lista de decisões críticas pendentes.

### 4.4 Frente B - Modelo da OS

#### Objetivo

Definir a OS como entidade central do processo.

#### Campos mínimos

Os nomes abaixo representam o modelo funcional mínimo. Na implementação GLPI, os campos de relacionamento devem seguir o padrão técnico do plugin, com prefixos de tabela quando aplicável.

```text
id
numero
vehicles_id
tipo_manutencao
origem
prioridade
status
descricao_problema
diagnostico
workshops_id
solicitante_id
responsavel_id
data_abertura
data_previsao_conclusao
data_conclusao
odometro_abertura
odometro_conclusao
custo_estimado
custo_final
aprovacao_status
aprovador_id
data_aprovacao
justificativa_aprovacao
bloqueia_viatura
observacoes
```

---

## 5. Marcos Sugeridos

### Marco 1 - Internacionalização e Fundação Técnica

**Status:** concluído.

- [x] Regras i18n consolidadas.
- [x] Catálogo base de tradução atualizado.
- [x] Seletor de idioma do plugin estabilizado.
- [x] Telas e fluxos principais preparados para `msgid` em inglês.
- [x] Critério definido: nenhum texto novo user-facing hardcoded.
- [x] Componente de alerta estabilizado.
- [x] Documentação de escopo atualizada.
- [x] Status executivo: frente consolidada; novos textos user-facing devem continuar entrando por `__($msgid, 'vehiclescheduler')` com `msgid` em inglês e tradução nos quatro locales.

### Marco 2 - Refactor Estrutural

**Status:** concluído.

- [x] Inventário de classes GLPI compatibility concluído em `src/Legacy/`.
- [x] Regras de domínio candidatas a `src/` mapeadas.
- [x] Telas em `front/` revisadas para manter entry points finos.
- [x] Helpers compartilhados revisados.
- [x] Padrão para novas classes em `src/` consolidado.
- [x] Autoload Composer com classmap para `src/Legacy/` validado.
- [x] Diretório legado removido e sem referências operacionais remanescentes.
- [x] Instalação e ativação do plugin validadas após a consolidação.
- [x] Status executivo: base estrutural concluída; refactors finos podem continuar por tela, sem bloquear a frente de i18n.

### Marco 3 - Revisão Visual e CSS

**Status:** concluído.

- [x] CSS em `public/css/pages/` mapeado.
- [x] Duplicidades principais identificadas.
- [x] Componentes visuais compartilhados definidos.
- [x] Padrão visual base validado estruturalmente em telas principais.
- [x] Status executivo: frente concluída; novas telas de manutenção devem reutilizar `public/css/core/components.css` antes de criar CSS próprio.

### Marco 4 - Preparação Técnica da Manutenção

**Status:** planejado.

- [ ] Modelo reduzido da OS definido.
- [ ] Campos mínimos da OS validados.
- [ ] Fluxo operacional reduzido documentado.
- [ ] Chaves de tradução do módulo de manutenção planejadas.
- [ ] Status executivo: documentação preparada; codificação funcional aguarda revisão/consolidação de CSS antes de avançar para telas novas.

### Marco 5 - Oficina MVP

- Cadastro simples de oficinas.
- Especialidades como filtro auxiliar.
- Permissões e listagem.

### Marco 6 - OS MVP

- Abertura de OS.
- Fluxo essencial.
- Diagnóstico, orçamento, aprovação e execução.

### Marco 7 - Integrações Operacionais

- Integração com incidente/checklist.
- Status operacional da viatura.
- Histórico e auditoria essencial.

### Marco 8 - Indicadores

- Dashboard de manutenção.
- Alertas essenciais.
- Relatórios básicos.
