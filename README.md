# SisViaturas

Fleet management and vehicle scheduling plugin for **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) is a GLPI plugin focused on vehicle reservation requests, approval flow, operational assignment, conflict validation, and dashboard visibility for day-to-day fleet operations.

## Current scope

The plugin currently targets workflows such as:
- vehicle reservation requests
- approval and rejection flow
- requester and management visibility by permission
- vehicle and driver assignment
- date/time conflict validation
- operational, management, and executive dashboards
- compact UI refinements for dense daily use

## Technical direction

The project follows a strict split between business logic and UI rendering.

### Backend / domain
Preferred location:
- `src/...`

Legacy-compatible area:
- `inc/*.class.php`

Typical responsibilities:
- ACL and authorization
- validation
- conflict detection
- business rules
- persistence rules
- service logic
- ticket integration
- reporting/aggregation
- cache logic

### Front / rendering
Preferred location:
- `front/*.php`

Typical responsibilities:
- page rendering
- layout composition
- buttons and field visibility
- entry-point flow
- backend/service orchestration
- CSS/JS asset loading

### AJAX endpoints
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

## Namespace and class conventions

For modern code in `src/`:
- use PSR-4 namespaces
- base namespace: `GlpiPlugin\Vehiclescheduler`
- mirror directory structure in namespaces
- import dependencies with `use`
- keep one main class/interface/trait per file when possible

Examples:
- `src/Service/ReservationConflictService.php`
- `src/Controller/ManagementController.php`

Thin entry-point files such as `front/*.php`, `ajax/*.php`, `setup.php`, and `hook.php` usually remain without namespace declarations.

## Database compatibility

For GLPI 11 compatibility:
- do not use `$DB->request($sql)` with raw SQL strings
- prefer structured criteria with `$DB->request(...)`
- use `$DB->doQuery($sql)` only when raw SQL is unavoidable
- iterate with `$DB->fetchAssoc(...)`

## setup.php and hook.php

`setup.php` should stay focused on plugin bootstrap, metadata, requirements, and config checks.

`hook.php` should stay focused on install, uninstall, and schema upgrade logic.

Schema changes should be idempotent and reinforced for existing installs.

## Configuration strategy

For simple plugin settings, prefer GLPI configuration storage instead of creating a dedicated custom config table without a strong reason.

## UI direction

SisViaturas favors an operational, compact, readable UI.

Patterns that fit the project direction:
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

## Suggested repository structure

```text
plugins/vehiclescheduler/
├── ajax/
├── front/
├── inc/                  # legacy-only while migrating
├── locales/
├── public/
│   ├── css/
│   └── js/
├── src/
├── templates/            # optional
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

## Installation

1. Place the plugin under `plugins/vehiclescheduler`.
2. Ensure dependencies are installed when applicable.
3. Open GLPI.
4. Go to **Setup > Plugins**.
5. Install and enable **SisViaturas**.

## Apache deployment examples

The repository may include Apache configuration examples to help administrators publish GLPI either at the web root or under a subdirectory.

### `glpi-root.conf.example`
Use this example when GLPI is published at the host root, for example:
- `http://server/`

This is the preferred option in environments where administrators want GLPI directly at the base URL.

### `glpi-subdir.conf.example`
Use this example when GLPI is published under a subdirectory, for example:
- `http://server/glpi/`

This is useful for environments where GLPI shares the same virtual host with other applications or where `/glpi` is the chosen canonical path.

### `glpi.conf`
This file represents the effective or current Apache virtual host configuration used in the target environment.

It can be used as:
- a working deployment reference
- a baseline for local adjustments
- a practical comparison point against the example configurations

## Root path compatibility

SisViaturas is designed to work with GLPI installations published either:
- at the web root, such as `http://server/`
- under a subdirectory, such as `http://server/glpi/`

For this reason, plugin URLs should rely on GLPI-aware helpers instead of hardcoded assumptions about `/glpi`.

Recommended approach:
- use `plugin_vehiclescheduler_get_root_doc()` for GLPI core URLs
- use `plugin_vehiclescheduler_get_front_url()` for plugin front controllers
- use `plugin_vehiclescheduler_get_public_url()` or the project equivalent for public assets

## Development guidelines

- Prefer `src/` for new/refactored backend code
- Follow PSR-12 in PHP code
- Keep cache abstractions aligned with PSR-6
- Reuse existing ACL helpers when available
- Keep comments and technical documentation in English
- Keep user-facing labels in Portuguese when that serves the product

## Contributing

Before broad changes:
- identify which layer owns the change
- verify GLPI 11 database compatibility
- check whether upgrade/version behavior is impacted
- avoid mixing UI fixes into domain classes
- avoid expanding legacy patterns without necessity

## Documentation map

- `AGENTS.md`: normative rules for AI/code generation
- `CODEX_HANDOFF.md`: practical implementation guidance for Codex
- `CHANGELOG.md`: release history and notable changes

## License

GPL v2+
