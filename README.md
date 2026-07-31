# explayouts_ui_api

JSON HTTP API and SPA shell for the Exponential Layouts admin UI on Exponential Legacy / Exponential 6. It serves the built layouts admin app assets from `design/standard` and exposes the JSON API the app expects under `/explayouts_ui_api/app/api/...`, replacing the Symfony backend of the upstream stack with plain eZ Publish module views and the `explayouts_core` services.

Exponential Legacy port inspired by the `netgen/layouts-ui` package (and the app endpoints of `netgen/layouts-core`).

## Key classes and views

| Item | Purpose |
|------|---------|
| `expLayoutsUIApplicationApi` (`classes/explayoutsuiapplicationapi.php`) | JSON API dispatcher: `handle( $parts )` routes `config`, `layouts`, `blocks`, `rules`, `mappings`, `transfer`, `collections`, `forms`, `parameters`, `versions`, `share` |
| `modules/explayouts_ui_api/app.php` | SPA shell (`/explayouts_ui_api/app`), API entry, preview and HTML block-edit form fragments |
| `modules/explayouts_ui_api/layouts.php`, `rules.php`, `blocks.php` | Standalone JSON module views built on the `explayouts_core` services |
| `design/standard/templates/explayouts_ui_api/app.tpl` | SPA bootstrap page (route prefix, base paths and form token meta tags) |

## Dependencies

- `explayouts` / `explayouts_core` — persistent value objects, services, block/layout type definitions (`explayouts.ini`)
- `ezformtoken` — CSRF validation for modifying requests

## Documentation

- [INSTALL.md](INSTALL.md) — activation
- [doc/USAGE.md](doc/USAGE.md) — all endpoints, curl examples, customization
- [doc/FAQ.md](doc/FAQ.md) — common questions
- [doc/TODO.md](doc/TODO.md) — known gaps
- [doc/SUPPORT.md](doc/SUPPORT.md) — how to get help
