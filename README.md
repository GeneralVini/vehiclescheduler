# SisViaturas

Fleet management and vehicle scheduling plugin for **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) is a GLPI plugin focused on vehicle reservation requests, approval flow, operational assignment, conflict validation, and dashboard visibility for day-to-day fleet operations.

## Current MVP Scope

The current project version is a functional MVP with:

- vehicle CRUD
- driver CRUD
- reservation/request workflow
- dashboard

The codebase also contains additional operational modules that may be present or evolving, such as maintenance, incidents, reports, checklists, fines, insurance claims, and theme/UI helpers.

## Requirements

- GLPI 11 installed and working
- PHP 8.1 or newer
- Composer
- Web server configured for GLPI

## Installation

Place the plugin under the GLPI plugins directory:

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Install PHP dependencies. The repository does not rely on a committed `vendor/` directory:

```bash
composer install
```

If `composer.lock` is missing or dependency versions intentionally need to be refreshed, run:

```bash
composer update
```

Then install and enable the plugin in GLPI:

1. Open GLPI in the browser.
2. Go to **Setup > Plugins**.
3. Install **SisViaturas / Vehicle Scheduler**.
4. Enable the plugin.

## Update

From the plugin directory:

```bash
git pull
composer install
```

Use `composer update` only when intentionally updating dependency versions.

## Profile Configuration

Requester and administrator/approver access is configured in the native GLPI profile screen.

Open the target profile in GLPI and use the **Gestão de Frota** tab added by the plugin. The form is rendered by `PluginVehicleschedulerProfile` and saved through `front/profile.form.php`.

The available plugin rights are:

- **Acesso ao Portal de Reservas**: allows users to create reservations and report incidents.
- **Acesso à Gestão de Frota**: allows access to dashboard, vehicles, drivers, maintenance, reports, and registrations. It may be configured as no access, read access, or write/CRUD access.
- **Aprovar/Rejeitar Reservas**: allows users to approve or reject reservation requests.

## Technical Direction

The project follows a strict split between business logic and UI rendering.

### Backend / Domain

Preferred location for new or refactored backend code:

- `src/...`

Current legacy-compatible domain classes still live in:

- `inc/*.class.php`

This means existing MVP business logic may still be in `inc/`, but new domain code and broader refactors should move toward PSR-4 classes under `src/`.

Typical backend responsibilities:

- ACL and authorization
- validation
- conflict detection
- business rules
- persistence rules
- service logic
- ticket integration
- reporting/aggregation
- cache logic
- search options

Backend/domain PHP classes must not contain screen layout, inline CSS, inline JavaScript, page composition, or button markup.

### Front / Rendering

Preferred location:

- `front/*.php`

Typical responsibilities:

- page rendering
- layout composition
- buttons and field visibility
- entry-point flow
- backend/service orchestration
- CSS/JS asset loading

### AJAX Endpoints

Preferred location:

- `ajax/*.php`

Typical responsibilities:

- async request handling
- thin endpoint orchestration
- delegating to backend/services

### Assets

- `public/css/*.css` for styling
- `public/js/*.js` for client behavior
- `locales/` for translations

## Namespace and Class Conventions

For modern code in `src/`:

- use PSR-4 namespaces
- use the base namespace `GlpiPlugin\Vehiclescheduler`
- mirror the directory structure in namespaces
- import dependencies with `use`
- keep one main class/interface/trait per file when possible

Examples:

- `src/Service/ReservationConflictService.php`
- `src/Controller/ManagementController.php`

Thin entry-point files such as `front/*.php`, `ajax/*.php`, `setup.php`, and `hook.php` usually remain without namespace declarations.

Legacy `inc/*.class.php` files may remain in the `PluginVehiclescheduler...` class format while the project is being migrated.

## Database Compatibility

For GLPI 11 compatibility:

- do not use `$DB->request($sql)` with raw SQL strings
- prefer structured criteria with `$DB->request(...)`
- use `$DB->doQuery($sql)` only when raw SQL is unavoidable
- iterate raw query results with `$DB->fetchAssoc(...)`

Do not place SQL/reporting logic in front-end rendering files.

## setup.php and hook.php

`setup.php` should stay focused on plugin bootstrap, metadata, requirements, and config checks.

`hook.php` should stay focused on install, uninstall, schema creation, and schema upgrade logic.

Schema changes should be idempotent and reinforced for existing installs.

## UI Direction

SisViaturas favors an operational, compact, readable UI.

Patterns aligned with the project direction:

- compact spacing at 100% zoom
- strong readability in KPI cards
- zebra striping in dense tables
- hover highlight on active rows
- concise operational date/time formatting
- coherent CSS patches for broad visual adjustments

Patterns to avoid:

- oversized headers or cards
- layouts that only work at reduced zoom
- UI fixes implemented inside backend classes

## Suggested Repository Structure

```text
plugins/vehiclescheduler/
├── ajax/
├── front/
├── inc/                  # legacy-compatible classes while migration occurs
├── locales/
├── public/
│   ├── css/
│   └── js/
├── src/                  # preferred location for new/refactored domain code
├── templates/            # optional
├── tools/
├── vendor/               # generated by composer install
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

## Apache Deployment Examples

The repository may include Apache configuration examples to help administrators publish GLPI either at the web root or under a subdirectory.

### `glpi-root.conf.example`

Use this example when GLPI is published at the host root, for example:

- `http://server/`

### `glpi-subdir.conf.example`

Use this example when GLPI is published under a subdirectory, for example:

- `http://server/glpi/`

### `glpi.conf`

This file represents the effective or current Apache virtual host configuration used in the target environment.

It can be used as:

- a working deployment reference
- a baseline for local adjustments
- a practical comparison point against the example configurations

## Root Path Compatibility

SisViaturas is designed to work with GLPI installations published either:

- at the web root, such as `http://server/`
- under a subdirectory, such as `http://server/glpi/`

For this reason, plugin URLs should rely on GLPI-aware helpers instead of hardcoded assumptions about `/glpi`.

Recommended approach:

- use `plugin_vehiclescheduler_get_root_doc()` for GLPI core URLs
- use `plugin_vehiclescheduler_get_front_url()` for plugin front controllers
- use `plugin_vehiclescheduler_get_public_url()` or the project equivalent for public assets

## Development Guidelines

- Prefer `src/` for new/refactored backend code.
- Keep existing legacy `inc/*.class.php` domain code stable unless the change requires touching it.
- Follow PSR-12 in PHP code.
- Keep cache abstractions aligned with PSR-6.
- Reuse existing ACL helpers when available.
- Keep comments and technical documentation in English.
- Keep user-facing labels in Portuguese when that serves the product.

## Documentation Map

- `AGENTS.md`: normative rules for AI/code generation
- `CODEX_HANDOFF.md`: practical implementation guidance for Codex
- `README_vehiclescheduler_pt-BR.md`: Brazilian Portuguese README
- `CHANGELOG.md`: release history and notable changes

## License

GPL v2+
