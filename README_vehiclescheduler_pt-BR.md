# SisViaturas

Plugin de gestão de frota e agendamento de veículos para **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) é um plugin do GLPI focado em solicitações de reserva de veículos, fluxo de aprovação, alocação operacional, validação de conflitos e visibilidade por dashboards para a operação diária da frota.

## Escopo Atual do MVP

A versão atual do projeto é um MVP funcional com:

- CRUD de veículos
- CRUD de motoristas
- solicitação de pedidos/reservas
- dashboard

O código também contém módulos operacionais adicionais que podem estar presentes ou em evolução, como manutenção, incidentes, relatórios, checklists, multas, sinistros e ajustes de tema/interface.

## Requisitos

- GLPI 11 instalado e funcionando
- PHP 8.1 ou superior
- Composer
- Servidor web configurado para o GLPI

## Instalação

Coloque o plugin no diretório de plugins do GLPI:

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Instale as dependências PHP. O repositório não depende de um diretório `vendor/` versionado:

```bash
composer install
```

Se o `composer.lock` não existir ou se for necessário atualizar intencionalmente as versões das dependências, execute:

```bash
composer update
```

Depois, instale e habilite o plugin no GLPI:

1. Abra o GLPI no navegador.
2. Acesse **Configurar > Plugins**.
3. Instale o **SisViaturas / Vehicle Scheduler**.
4. Habilite o plugin.

## Atualização

A partir do diretório do plugin:

```bash
git pull
composer install
```

Use `composer update` somente quando a intenção for atualizar versões de dependências.

## Configuração de Perfil

As permissões de solicitante e administrador/aprovador são configuradas na tela nativa de Perfil do GLPI.

Abra o perfil desejado no GLPI e use a aba **Gestão de Frota** adicionada pelo plugin. O formulário é renderizado por `PluginVehicleschedulerProfile` e salvo por `front/profile.form.php`.

As permissões disponíveis do plugin são:

- **Acesso ao Portal de Reservas**: permite solicitar reservas e reportar incidentes.
- **Acesso à Gestão de Frota**: permite acessar dashboard, veículos, motoristas, manutenções, relatórios e cadastros. Pode ser configurado como sem acesso, leitura ou escrita/CRUD.
- **Aprovar/Rejeitar Reservas**: permite aprovar ou rejeitar solicitações de reserva.

## Direção Técnica

O projeto segue uma separação rígida entre lógica de negócio e renderização de interface.

### Backend / Domínio

Local preferencial para código backend novo ou refatorado:

- `src/...`

Classes de domínio legadas e compatíveis ainda estão em:

- `inc/*.class.php`

Isso significa que a lógica de negócio existente do MVP ainda pode estar em `inc/`, mas novos códigos de domínio e refatorações mais amplas devem caminhar para classes PSR-4 em `src/`.

Responsabilidades típicas do backend:

- ACL e autorização
- validação
- detecção de conflitos
- regras de negócio
- regras de persistência
- lógica de serviços
- integração com tickets
- relatórios/agregações
- lógica de cache
- opções de busca

Classes PHP de backend/domínio não devem conter layout de tela, CSS inline, JavaScript inline, composição de página ou marcação de botões.

### Front / Renderização

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

## Convenções de Namespace e Classes

Para código moderno em `src/`:

- usar namespaces PSR-4
- usar o namespace base `GlpiPlugin\Vehiclescheduler`
- espelhar a estrutura de diretórios nos namespaces
- importar dependências com `use`
- manter uma classe/interface/trait principal por arquivo sempre que possível

Exemplos:

- `src/Service/ReservationConflictService.php`
- `src/Controller/ManagementController.php`

Arquivos de entrada finos como `front/*.php`, `ajax/*.php`, `setup.php` e `hook.php` normalmente permanecem sem declaração de namespace.

Arquivos legados `inc/*.class.php` podem continuar no formato de classe `PluginVehiclescheduler...` enquanto a migração ocorre.

## Compatibilidade com Banco de Dados

Para compatibilidade com GLPI 11:

- não usar `$DB->request($sql)` com SQL bruto em string
- preferir critérios estruturados com `$DB->request(...)`
- usar `$DB->doQuery($sql)` apenas quando SQL bruto for inevitável
- iterar resultados de consultas brutas com `$DB->fetchAssoc(...)`

Não coloque SQL ou lógica de relatórios em arquivos de renderização front-end.

## setup.php e hook.php

`setup.php` deve permanecer focado em bootstrap do plugin, metadados, requisitos e verificações de configuração.

`hook.php` deve permanecer focado em instalação, desinstalação, criação de esquema e lógica de upgrade de esquema.

Mudanças de esquema devem ser idempotentes e reforçadas para instalações existentes.

## Direção de Interface

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

## Estrutura Sugerida do Repositório

```text
plugins/vehiclescheduler/
├── ajax/
├── front/
├── inc/                  # classes legadas/compatíveis enquanto a migração ocorre
├── locales/
├── public/
│   ├── css/
│   └── js/
├── src/                  # local preferencial para domínio novo/refatorado
├── templates/            # opcional
├── tools/
├── vendor/               # gerado por composer install
├── CHANGELOG.md
├── composer.json
├── composer.lock
├── hook.php
├── LICENSE
├── README.md
├── README_vehiclescheduler_pt-BR.md
├── setup.php
└── plugin.xml
```

## Exemplos de Publicação Apache

O repositório pode incluir exemplos de configuração Apache para ajudar administradores a publicar o GLPI tanto na raiz do host quanto em um subdiretório.

### `glpi-root.conf.example`

Use este exemplo quando o GLPI for publicado na raiz do host, por exemplo:

- `http://servidor/`

### `glpi-subdir.conf.example`

Use este exemplo quando o GLPI for publicado em um subdiretório, por exemplo:

- `http://servidor/glpi/`

### `glpi.conf`

Esse arquivo representa a configuração Apache efetiva ou atual utilizada no ambiente de destino.

Ele pode ser usado como:

- referência de deploy funcional
- base para ajustes locais
- ponto de comparação prático com os arquivos de exemplo

## Compatibilidade com Raiz e Subdiretório

SisViaturas foi pensado para funcionar com instalações GLPI publicadas tanto:

- na raiz web, como `http://servidor/`
- em subdiretório, como `http://servidor/glpi/`

Por isso, as URLs do plugin devem usar helpers compatíveis com o GLPI em vez de assumir caminhos fixos como `/glpi`.

Abordagem recomendada:

- usar `plugin_vehiclescheduler_get_root_doc()` para URLs do core do GLPI
- usar `plugin_vehiclescheduler_get_front_url()` para controllers `front` do plugin
- usar `plugin_vehiclescheduler_get_public_url()` ou helper equivalente para assets públicos

## Diretrizes de Desenvolvimento

- Preferir `src/` para código backend novo ou refatorado.
- Manter estável o código de domínio legado em `inc/*.class.php`, exceto quando a alteração exigir tocá-lo.
- Seguir PSR-12 no PHP.
- Manter abstrações de cache alinhadas ao PSR-6.
- Reutilizar helpers ACL existentes quando disponíveis.
- Manter comentários e documentação técnica em inglês.
- Manter rótulos voltados ao usuário em português quando isso fizer sentido para o produto.

## Mapa de Documentação

- `AGENTS.md`: regras normativas para geração por IA/código
- `CODEX_HANDOFF.md`: orientação prática de implementação para Codex
- `README.md`: README em inglês
- `CHANGELOG.md`: histórico de versões e mudanças relevantes

## Licença

GPL v2+
