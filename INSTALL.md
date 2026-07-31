# Installing explayouts_ui_api

## Requirements

- Exponential Legacy / Exponential 6, PHP 8.1+
- `extension/explayouts` — value objects, `explayouts_*` tables, `explayouts.ini` block/layout type definitions
- `extension/explayouts_core` — service classes the API handlers call
- `extension/ezformtoken` — CSRF token validation for modifying requests

## 1. Put the extension in place

```
extension/explayouts_ui_api
```

## 2. Activate

Add it (after its dependencies) to `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=explayouts
ActiveExtensions[]=explayouts_core
ActiveExtensions[]=explayouts_ui_api
```

or per siteaccess via `ActiveAccessExtensions[]` in `settings/siteaccess/<access>/site.ini.append.php`. Activate it for the admin siteaccess — the app runs inside the authenticated admin session.

## 3. Regenerate autoloads and clear caches

```bash
php bin/php/ezpgenerateautoloads.php -e
php bin/php/ezcache.php --clear-all --purge --allow-root-user
```

## 4. Settings shipped with the extension

- `settings/module.ini.append.php` — registers the `explayouts_ui_api` module with views `app`, `layouts`, `rules`, `blocks` (all `read` function).
- `settings/design.ini.append.php` — registers the design extension so the SPA assets under `design/standard/` (javascript, stylesheets, fonts, images, vendor) are served.

## 5. Verify

Open `/explayouts_ui_api/app` in the admin siteaccess — the layouts app shell should load. `GET /explayouts_ui_api/app/api/config` returns JSON including the current form token.

## Companion extension

`explayouts_ui` adds the "Exponential Layouts UI" admin menu with legacy screens whose "Edit in modern editor" links open this app; activate it alongside for the full admin experience.
