Exponential Layouts UI API
==========================

General description
-------------------

Exponential Layouts UI API (`explayouts_ui_api`) is the JSON HTTP API and SPA shell for the Exponential Layouts admin UI on Exponential 6 / Exponential Legacy. It serves the built layouts admin app assets from `design/standard` and exposes the JSON API the app expects under `/explayouts_ui_api/app/api/...`, replacing the Symfony backend of the upstream stack with plain Exponential Legacy module views and the `explayouts_core` services.

It is an Exponential Legacy port inspired by the `netgen/layouts-ui` package (and the app endpoints of `netgen/layouts-core`).

This extension provides the following capabilities:

- SPA shell - Bootstraps the layouts admin app at `/explayouts_ui_api/app` (optionally `/app/<locale>`), including the meta tags the JavaScript reads and the bundled CSS/JS.
- JSON API - A single dispatcher routing `config`, `layouts`, `blocks`, `rules`, `mappings`, `transfer`, `collections`, `forms`, `parameters`, `versions` and `share` resources under `/explayouts_ui_api/app/api/...`.
- Layout lifecycle over HTTP - Create draft layouts, publish them, delete drafts, list versions and manage share tokens.
- Block editing over HTTP - Create, load and update blocks (name, view type, position, parameters) per layout and locale, plus HTML form fragments for the block edit sidebar.
- Rules and mappings - List layout mapping rules with targets and conditions, and rule counts per layout.
- CSRF-protected writes - All modifying requests validate the eZ form token via the `ezformtoken` extension.
- Standalone JSON views - Simple read-only module views for layouts, rules and blocks outside the SPA dispatcher.

Features
--------

The following features are provided by the Exponential Layouts UI API extension:

- Central dispatcher - `expLayoutsUIApplicationApi` (`classes/explayoutsuiapplicationapi.php`) routes every `/app/api/<resource>` request via `handle( $parts )`; all responses are `application/json`, debug output is disabled in the dispatcher so responses stay clean, and list responses use the `values` / `total` shape the app expects.
- App shell module - `modules/explayouts_ui_api/app.php` serves the SPA shell, the API entry, the layout preview (`/explayouts_ui_api/app/preview/<layout_id>`) and the HTML block-edit form fragments.
- Bootstrap template - `design/standard/templates/explayouts_ui_api/app.tpl` renders the meta tags the JavaScript reads: `nglayouts-route-prefix` (set to `/explayouts_ui_api`), `nglayouts-base-path`, `ngcb-base-path` and `ezxform-token`.
- Configuration endpoints - `GET /app/api/config` (including the current CSRF token), `GET /app/api/config/layout_types` (layout types and their zones from `explayouts.ini`) and `GET /app/api/config/block_types` (block types grouped as `block_types` and `block_type_groups`).
- Layout endpoints - `GET/POST /app/api/layouts`, `GET /app/api/layouts/<id>`, `POST /app/api/layouts/<id>/publish` and `DELETE /app/api/layouts/<id>/draft`. Created layouts are drafts (`status=1`) with zones created from the layout type definition; the identifier is generated from the name if not provided.
- Block endpoints - `GET /app/api/<locale>/layouts/<layout_id>/blocks`, `GET/POST/PUT/PATCH /app/api/<locale>/blocks/<id>` and `POST /app/api/<locale>/blocks`. Updates accept `name`, `view_type`, `position` and `parameters` (object keyed by parameter name).
- Rule and mapping endpoints - `GET /app/api/rules`, `GET /app/api/rules/<id>` and `GET /app/api/mappings` (rule counts per `layout_id`).
- Collection endpoints - `/app/api/collections/...` manage collections and collection items for blocks with collections (backed by `expLayoutsCoreCollectionService`).
- Transfer, forms, parameters, versions, share - `/app/api/transfer/...` (import/export via `expLayoutsImporter` / `expLayoutsExporter`), `/app/api/forms/...` and `/app/api/parameters/...` (form/parameter metadata for the block edit UI), `GET /app/api/versions/<layout_id>` (draft and published versions) and `GET|POST /app/api/share/<layout_id>` (share tokens, stored in the `explayouts_share` table, created on demand).
- HTML form fragments - `GET /explayouts_ui_api/app/<locale>/blocks/<id>/edit` (wrapper, `form_block_edit.tpl`) and `.../form` (the `<form>`, `form_block_fields.tpl`); the form submits to `POST /explayouts_ui_api/app/api/<locale>/blocks/<id>` and returns the updated block as JSON.
- CSRF protection - `POST`, `PUT`, `PATCH` and `DELETE` requests must carry the eZ form token, either as an `X-CSRF-Token` HTTP header or an `ezxform_token` form field; the current token is available from `GET /app/api/config`.
- Flexible request bodies - `POST` bodies may be `application/x-www-form-urlencoded` or `application/json`; `requestData()` in `expLayoutsUIApplicationApi` falls back to `php://input` when `$_POST` is empty.
- Standalone JSON module views - `/explayouts_ui_api/layouts/(LayoutID)/<id>`, `/explayouts_ui_api/rules/(RuleID)/<id>` and `/explayouts_ui_api/blocks/(ZoneID)/<id>` are simple read-only views built on the `explayouts_core` services.

Version
-------

- The current version of Exponential Layouts UI API is 1.0.0
- Last Major update: July 30, 2026

Copyright
---------

- Exponential Layouts UI API is copyright 1998 - 2026 7x
- See: [LICENSE.md](LICENSE.md) for more information on the terms of the copyright and license

License
-------

Exponential Layouts UI API is licensed under the GNU General Public License.

The complete license agreement is included in the [LICENSE.md](LICENSE.md) file.

Exponential Layouts UI API is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License or at your
option a later version.

Exponential Layouts UI API is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The GNU GPL gives you the right to use, modify and redistribute
Exponential Layouts UI API under certain conditions. The GNU GPL license
is distributed with the software, see the file LICENSE.md.

It is also available at http://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License
along with Exponential Layouts UI API in LICENSE.md. If not, see http://www.gnu.org/licenses/.

Using Exponential Layouts UI API under the terms of the GNU GPL is free (as in freedom).

For more information or questions please contact
info@se7enx.com

Requirements
------------

The following requirements exists for using the Exponential Layouts UI API extension:

Exponential version
- Make sure you use Exponential 6 / eZ Publish Legacy (required) or higher.

PHP version
- Make sure you have PHP 8.1 or higher.

Sibling extensions
- `extension/explayouts` — value objects, `explayouts_*` tables, `explayouts.ini` block/layout type definitions.
- `extension/explayouts_core` — service classes the API handlers call.
- `extension/ezformtoken` — CSRF token validation for modifying requests.

Siteaccess
- Activate the extension for the admin siteaccess — the app runs inside the authenticated admin session.

Installation
------------

In short: place the extension in `extension/explayouts_ui_api`, activate it after its dependencies (`explayouts`, `explayouts_core`) via `site.ini` `[ExtensionSettings] ActiveExtensions[]` (or per siteaccess via `ActiveAccessExtensions[]`) for the admin siteaccess, then regenerate autoloads and clear all caches. Verify by opening `/explayouts_ui_api/app` in the admin siteaccess — the layouts app shell should load, and `GET /explayouts_ui_api/app/api/config` returns JSON including the current form token. The companion extension `explayouts_ui` adds the "Exponential Layouts UI" admin menu whose "Edit in modern editor" links open this app.

See [INSTALL.md](INSTALL.md) for the full step-by-step installation instructions, including the settings shipped with the extension (`module.ini` with views `app`, `layouts`, `rules`, `blocks`; `design.ini` for the SPA assets).

Usage
-----

Key classes and views:

| Item | Purpose |
|------|---------|
| `expLayoutsUIApplicationApi` (`classes/explayoutsuiapplicationapi.php`) | JSON API dispatcher: `handle( $parts )` routes `config`, `layouts`, `blocks`, `rules`, `mappings`, `transfer`, `collections`, `forms`, `parameters`, `versions`, `share` |
| `modules/explayouts_ui_api/app.php` | SPA shell (`/explayouts_ui_api/app`), API entry, preview and HTML block-edit form fragments |
| `modules/explayouts_ui_api/layouts.php`, `rules.php`, `blocks.php` | Standalone JSON module views built on the `explayouts_core` services |
| `design/standard/templates/explayouts_ui_api/app.tpl` | SPA bootstrap page (route prefix, base paths and form token meta tags) |

Endpoint overview (all under `/explayouts_ui_api`, responses are `application/json`):

| Endpoint | Purpose |
|----------|---------|
| `GET /app/api/config`, `GET /app/api/config/layout_types`, `GET /app/api/config/block_types` | App configuration, CSRF token, layout and block type definitions |
| `GET/POST /app/api/layouts`, `GET /app/api/layouts/<id>`, `POST /app/api/layouts/<id>/publish`, `DELETE /app/api/layouts/<id>/draft` | Layout lifecycle |
| `GET /app/api/<locale>/layouts/<layout_id>/blocks`, `GET/POST/PUT/PATCH /app/api/<locale>/blocks/<id>`, `POST /app/api/<locale>/blocks` | Block management |
| `GET /app/api/rules`, `GET /app/api/rules/<id>`, `GET /app/api/mappings` | Mapping rules and per-layout rule counts |
| `/app/api/collections/...` | Collection and collection item management |
| `/app/api/transfer/...`, `/app/api/forms/...`, `/app/api/parameters/...` | Import/export and block edit form metadata |
| `GET /app/api/versions/<layout_id>`, `GET|POST /app/api/share/<layout_id>` | Layout versions and share tokens |
| `GET /app/<locale>/blocks/<id>/edit`, `GET /app/<locale>/blocks/<id>/form` | HTML block-edit form fragments |
| `GET /app/preview/<layout_id>` | Layout preview |

Creating a layout:

```
POST /explayouts_ui_api/app/api/layouts
{ "name": "My layout", "layout_type": "2_column" }
```

Modifying requests must carry the eZ form token, either as an `X-CSRF-Token` HTTP header or an `ezxform_token` form field; fetch the current token from `GET /app/api/config`. Use placeholder credentials in examples — never commit real credentials.

See [doc/USAGE.md](doc/USAGE.md) for exhaustive scenarios: every endpoint with request/response bodies, a complete curl session, the HTML form fragment flow, implementation notes, and the full customization guide covering the settings layer (INI cascade — everything the API exposes is defined in `explayouts.ini` and overridable without code changes; access control via module policies), the template layer (design override cascade for `app.tpl` and the form templates — the supported way to swap CSS/JS bundles) and the PHP layer (building against the JSON contract, adding block parameters via handlers, custom read-only JSON views).

Documentation
-------------

| Document | Description |
|----------|-------------|
| [INSTALL.md](INSTALL.md) | Step-by-step installation: activation, dependencies, shipped settings, verification |
| [doc/USAGE.md](doc/USAGE.md) | All endpoints, curl examples, form fragments, customization layers |
| [doc/FAQ.md](doc/FAQ.md) | Answers to the most common questions and problems |
| [doc/TODO.md](doc/TODO.md) | Known gaps and planned improvements |
| [doc/SUPPORT.md](doc/SUPPORT.md) | How and where to get help |
| [LICENSE.md](LICENSE.md) | The complete GNU General Public License agreement |

Troubleshooting
---------------

Read the FAQ
- Some problems are more common than others. The most common ones are listed in [doc/FAQ.md](doc/FAQ.md).

Use our support systems
- If you have questions not handled by this document or the FAQ, you can reach us via [7x : se7enx.com](https://se7enx.com).
- If you find a bug or defect, please report it to the [Exponential Layouts UI API: Issue Tracker](https://github.com/se7enxweb/explayouts_ui_api/issues).
