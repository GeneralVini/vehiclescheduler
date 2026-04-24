# SisViaturas

Plugin de gestão de frota e agendamento de veículos para **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) é um plugin do GLPI focado em solicitações de reserva de veículos, fluxo de aprovação, alocação operacional, validação de conflitos e visibilidade por dashboards para a operação diária da frota.

## Escopo atual

Atualmente, o plugin cobre fluxos como:
- solicitações de reserva de veículos
- fluxo de aprovação e rejeição
- visibilidade para solicitantes e gestão conforme permissão
- vinculação de veículos e motoristas
- validação de conflitos de data e horário
- dashboards operacional, gerencial e executivo
- refinamentos de interface compacta para uso diário intenso

## Direção técnica

O projeto segue uma separação rígida entre lógica de negócio e renderização de interface.

### Backend / domínio
Local preferencial:
- `src/...`

Área legada compatível:
- `inc/*.class.php`

Responsabilidades típicas:
- ACL e autorização
- validação
- detecção de conflitos
- regras de negócio
- regras de persistência
- lógica de serviços
- integração com tickets
- relatórios/agregações
- lógica de cache

### Front / renderização
Local preferencial:
- `front/*.php`

Responsabilidades típicas:
- renderização de páginas
- composição de layout
- botões e visibilidade de campos
- fluxo dos entry points
- orquestração do backend/serviços
- carregamento de assets CSS/JS

### Endpoints AJAX
Local preferencial:
- `ajax/*.php`

Responsabilidades típicas:
- tratamento de requisições assíncronas
- orquestração enxuta do endpoint
- delegação para backend/serviços

### Assets
- `public/css/*.css` para estilos
- `public/js/*.js` para comportamento no cliente
- `locales/` para traduções

## Convenções de namespace e classes

Para código moderno em `src/`:
- usar namespaces PSR-4
- namespace base: `GlpiPlugin\Vehiclescheduler`
- espelhar a estrutura de diretórios nos namespaces
- importar dependências com `use`
- manter uma classe/interface/trait principal por arquivo sempre que possível

Exemplos:
- `src/Service/ReservationConflictService.php`
- `src/Controller/ManagementController.php`

Arquivos de entrada finos como `front/*.php`, `ajax/*.php`, `setup.php` e `hook.php` normalmente permanecem sem declaração de namespace.

## Compatibilidade com banco de dados

Para compatibilidade com GLPI 11:
- não usar `$DB->request($sql)` com SQL bruto em string
- preferir critérios estruturados com `$DB->request(...)`
- usar `$DB->doQuery($sql)` apenas quando SQL bruto for inevitável
- iterar com `$DB->fetchAssoc(...)`

## setup.php e hook.php

`setup.php` deve permanecer focado em bootstrap do plugin, metadados, requisitos e verificações de configuração.

`hook.php` deve permanecer focado em instalação, desinstalação e lógica de upgrade de esquema.

Mudanças de esquema devem ser idempotentes e reforçadas para instalações existentes.

## Estratégia de configuração

Para configurações simples do plugin, prefira o armazenamento de configuração nativo do GLPI em vez de criar uma tabela customizada dedicada sem necessidade forte.

## Direção de interface

SisViaturas privilegia uma interface operacional, compacta e legível.

Padrões alinhados ao projeto:
- espaçamento compacto em zoom de 100%
- boa legibilidade em cards de KPI
- zebra striping em tabelas densas
- destaque em hover na linha ativa
- formatação operacional concisa para data e hora
- patches CSS coerentes para ajustes visuais amplos

Padrões a evitar:
- cabeçalhos ou cards superdimensionados
- layouts que só funcionam em zoom reduzido
- correções visuais implementadas dentro de classes de backend

## Estrutura sugerida do repositório

```text
plugins/vehiclescheduler/
├── ajax/
├── front/
├── inc/                  # legado enquanto a migração ocorre
├── locales/
├── public/
│   ├── css/
│   └── js/
├── src/
├── templates/            # opcional
├── tools/
├── vendor/
├── CHANGELOG.md
├── composer.json
├── glpi-root.conf.example
├── glpi-subdir.conf.example
├── glpi.conf
├── hook.php
├── LICENSE
├── README.md
├── setup.php
├── vehiclescheduler.png
└── vehiclescheduler.xml
```

## Instalação

1. Coloque o plugin em `plugins/vehiclescheduler`.
2. Garanta que as dependências estejam instaladas, quando aplicável.
3. Abra o GLPI.
4. Vá em **Configurar > Plugins**.
5. Instale e habilite o **SisViaturas**.

## Exemplos de publicação Apache

O repositório pode incluir exemplos de configuração Apache para ajudar administradores a publicar o GLPI tanto na raiz do host quanto em um subdiretório.

### `glpi-root.conf.example`
Use este exemplo quando o GLPI for publicado na raiz do host, por exemplo:
- `http://servidor/`

Essa é a opção preferencial em ambientes onde se deseja o GLPI diretamente na URL base.

### `glpi-subdir.conf.example`
Use este exemplo quando o GLPI for publicado em um subdiretório, por exemplo:
- `http://servidor/glpi/`

Isso é útil em ambientes onde o GLPI compartilha o mesmo virtual host com outras aplicações ou quando `/glpi` é o caminho canônico escolhido.

### `glpi.conf`
Esse arquivo representa a configuração Apache efetiva ou atual utilizada no ambiente de destino.

Ele pode ser usado como:
- referência de deploy funcional
- base para ajustes locais
- ponto de comparação prático com os arquivos de exemplo

## Compatibilidade com raiz e subdiretório

SisViaturas foi pensado para funcionar com instalações GLPI publicadas tanto:
- na raiz web, como `http://servidor/`
- em subdiretório, como `http://servidor/glpi/`

Por isso, as URLs do plugin devem usar helpers compatíveis com o GLPI em vez de assumir caminhos fixos como `/glpi`.

Abordagem recomendada:
- usar `plugin_vehiclescheduler_get_root_doc()` para URLs do core do GLPI
- usar `plugin_vehiclescheduler_get_front_url()` para controllers `front` do plugin
- usar `plugin_vehiclescheduler_get_public_url()` ou helper equivalente para assets públicos

## Diretrizes de desenvolvimento

- Preferir `src/` para código backend novo ou refatorado
- Seguir PSR-12 no PHP
- Manter abstrações de cache alinhadas ao PSR-6
- Reutilizar helpers ACL existentes quando disponíveis
- Manter comentários e documentação técnica em inglês
- Manter rótulos voltados ao usuário em português quando isso fizer sentido para o produto

## Contribuição

Antes de mudanças amplas:
- identificar qual camada é responsável pela mudança
- verificar compatibilidade com banco no GLPI 11
- checar se o comportamento de upgrade/versão será impactado
- evitar misturar correções de UI em classes de domínio
- evitar expandir padrões legados sem necessidade

## Mapa de documentação

- `AGENTS.md`: regras normativas para geração por IA/código
- `CODEX_HANDOFF.md`: orientação prática de implementação para Codex
- `CHANGELOG.md`: histórico de versões e mudanças relevantes

## Licença

GPL v2+
