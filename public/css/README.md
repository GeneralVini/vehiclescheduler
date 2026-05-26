# SisViaturas CSS Architecture

## Goal
Keep a single CSS entry point for the plugin while avoiding a monolithic stylesheet
with duplicated page rules.

## Entry point
- `app.css`: only file loaded by PHP screens

## Layers
- `core/`: shared tokens, layout primitives, reusable components, compatibility base
- `pages/`: screen-scoped rules only

## Current transition strategy
- `core/legacy-base.css` contains the previous shared application styles
- `core/components.css` contains shared compact UI components extracted from
  repeated page patterns
- `pages/*.css` wrap the existing page files while we migrate them gradually
- legacy top-level files such as `management.css` and `schedule.css` are still used
  internally through the new page wrappers

## Rules for future changes
- Shared colors, spacing, shadows, radii, and typography must live in `core/`
- Reusable UI pieces such as buttons, cards, badges, tables, headers, and form
  fields must live in `core/`
- Page files must be scoped by a page root such as `.vs-page-management` or
  `#vs-schedule-queue-root`
- If the same selector or visual pattern appears on more than one screen, move it
  out of the page file into `core/`
- New screens should prefer `pages/<module>.css` instead of inline `<style>` blocks

## Migration direction
1. Remove inline `<style>` blocks from `front/*.php`
2. Move repeated rules from page files into `core/`
3. Keep `app.css` as the single runtime entry point

## Current inventory
Snapshot reviewed on 2026-05-25 after Marco 3:

| Area | Files | Notes |
| --- | ---: | --- |
| Admin / dashboard | 2 | `admin-dashboard.css`, `dashboard.css` |
| Calendar / booking | 2 | `calendar.css`, `booking.css` |
| Checklists | 4 | list, form, items, response form |
| Drivers / fines | 6 | grid, form, fines page, fine form, fine tab, fine render |
| Vehicles / reports | 3 | grid, form, vehicle report form |
| Incidents / claims / maintenance | 3 | form-level styles for operational records |
| Reports | 2 | report landing and view |
| Settings | 2 | config and theme config |
| Page wrappers | 3 | management, schedule, schedule form |
| Shared core | 4 | compatibility base, components, feedback, flash |
| Root entry / legacy wrappers | 4 | app entry plus top-level compatibility files |

Total CSS files: 35.

Removed orphan files:
- `core/vehicle-grid.css`
- `pages/driver-list.css`
- `pages/vehicle-list.css`

Largest page files:
- `driver-grid.css`
- `calendar.css`
- `admin-dashboard.css`
- `driverfine.render.css`
- `vehicle-grid.css`

## Consolidation targets
- Grid toolbar, search, filters, result count, table shell, empty state, action link,
  badges, and responsive behavior are repeated between vehicle, driver, and incident
  list screens.
- Shared compact pills, row actions, and table panels now have base selectors in
  `core/components.css`.
- Form cards, labels, hint text, action bars, and validation spacing are repeated
  across vehicle, driver, incident, insurance claim, maintenance, checklist, and fine
  forms.
- List and grid variants should converge into shared core components before new
  maintenance screens are expanded.

## Refactor order
1. Create shared core selectors for operational grids.
2. Migrate vehicle, driver, and incident grids to shared classes while preserving
   their page-specific modifiers.
3. Create shared form component rules for page cards, field grids, labels, hints,
   action bars, and destructive actions.
4. Remove compatibility wrapper files once no screen imports them directly.
5. Validate the main management, vehicle, driver, incident, schedule, checklist, and
   maintenance screens visually after each CSS reduction.
