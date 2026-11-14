# Using explayouts_ui_api

## App shell

The SPA is bootstrapped by the `app` module view:

```
/explayouts_ui_api/app
/explayouts_ui_api/app/<locale>
```

`modules/explayouts_ui_api/app.php` renders `design:explayouts_ui_api/app.tpl` with the meta tags the JavaScript reads:

- `explayouts-route-prefix` (set to `/explayouts_ui_api`)
- `explayouts-base-path`
- `ngcb-base-path`
- `ezxform-token`

The bundled CSS/JS is loaded from this extension's `design/standard` directory.

### An installation served from a sub path

Every file the editor names carries the installation's web root, as the template operator `ezroot('no')` writes it,
so an installation served from a sub path (`https://example.org/sub/path/`) finds them: the favicons of `app.tpl`, the
block type icons of `GET .../config/block_types` (`/sub/path/extension/explayouts_ui_api/design/standard/images/...`)
and the preview images of collection items (`/sub/path/var/<site>/storage/images/...`, or the address of the cluster's
file server where one is configured). The favicon `manifest.json` and `browserconfig.xml` name their icons beside them,
relative to their own address. The gallery and carousel previews come from explayouts' block handlers, which write
their images the same way (`expLayoutsSafeValue::webRootUrl()`).

## API base path

All API responses are `application/json` (debug output is disabled in the dispatcher so responses stay clean):

```
/explayouts_ui_api/app/api/<resource>
```

List responses use the `values` / `total` shape the app expects.

### Authentication and CSRF

The app runs inside the authenticated Exponential admin session. `POST`, `PUT`, `PATCH` and `DELETE` requests must carry the eZ form token, either as:

1. `X-CSRF-Token` HTTP header, or
2. `ezxform_token` form field.

The current token is available from `GET /explayouts_ui_api/app/api/config`:

```json
{
  "csrf_token": "...",
  "automatic_cache_clear": true,
  "edition": "Open Source"
}
```

## Endpoints

### Configuration

```
GET /app/api/config
GET /app/api/config/layout_types
GET /app/api/config/block_types
```

`layout_types` returns available layout types and their zones from `explayouts.ini`; `block_types` returns block types grouped as `block_types` and `block_type_groups`.

### Layouts

```
GET    /app/api/layouts
GET    /app/api/layouts/<id>
POST   /app/api/layouts
POST   /app/api/layouts/<id>/publish
DELETE /app/api/layouts/<id>/draft
```

`POST /app/api/layouts` expects:

```json
{ "name": "My layout", "layout_type": "2_column" }
```

The identifier is generated from the name if not provided. The created layout is a draft (`status=1`) and its zones are created from the layout type definition. `publish` sets `status=2`; `DELETE .../draft` removes the layout with its zones and blocks.

### Blocks

```
GET    /app/api/<locale>/layouts/<layout_id>/blocks
GET    /app/api/<locale>/blocks/<id>
POST   /app/api/<locale>/blocks
POST   /app/api/<locale>/blocks/<id>
PUT    /app/api/<locale>/blocks/<id>
PATCH  /app/api/<locale>/blocks/<id>
```

Create body:

```json
{
  "layout_id": 2,
  "zone_identifier": "left",
  "definition_identifier": "text",
  "name": "Hello",
  "parent_position": 0
}
```

Update accepts `name`, `view_type`, `item_view_type`, `position` and `parameters` (object keyed by parameter name).
A block's JSON carries its `view_type` and `item_view_type`.

- `view_type` must be a view type of the block's definition (`ViewTypes[]`) or the one the block already has; anything
  else answers `400 {"error":"Unknown view type for this block."}`.
- `item_view_type` must be an item view type of the definition, `""` (none: the design draws the view type's own) or
  the one the block already has; anything else, a value that is not text among it, answers
  `400 {"error":"Unknown item view type for this block."}` and changes nothing. The list is explayouts'
  `expLayoutsBlockHandlerFactory::itemViewTypes()`: the handler's own list, then `ItemViewTypes[<item view type>]=<label>`
  of `[BlockDefinition_<identifier>]`. Every view type offers all of them.

The frontend templates include the templates named by both values, which is why nothing else is stored.

### Block definitions of the previewed site

In an administration (a siteaccess whose `SiteDesign` begins with `admin`), the editor reads the block definitions from
the settings of the siteaccess it previews in (`[PreviewSettings] SiteAccess`, see explayouts'
`expLayoutsPreviewSiteAccess`), through explayouts' `expLayoutsBlockHandlerFactory::useSiteAccess()`: the block types
it offers (including the `Group=admin` check), the view types and item view types of the block form and the checks of a
save. A design keeps the block settings it adds in its own siteaccess, so this is where the editor finds them:

```ini
# extension/mytheme/settings/siteaccess/mysite/explayouts.ini.append.php
[BlockSettings]
AvailableBlocks[]=my_block

[BlockDefinition_list]
ViewTypes[]=grid_tiles
ViewTypeNames[grid_tiles]=Tiles
ItemViewTypes[tile]=Tile
```

The block form's "Item view type" select offers every item view type for every view type, with "Default of the view
type" first; a stored item view type the list does not offer stays selected, so saving the block does not change it
unasked.

### Rules and mappings

```
GET /app/api/rules
GET /app/api/rules/<id>
GET /app/api/mappings
```

`rules` lists layout mapping rules with targets and conditions; `mappings` returns rule counts per `layout_id`.

### Collections

```
/app/api/collections/...
```

Collection and collection item management for blocks with collections (backed by `expLayoutsCoreCollectionService`).

### Transfer, forms, parameters, versions, share

- `/app/api/transfer/...` — import/export of layouts and rules (works with `expLayoutsImporter` / `expLayoutsExporter`). `GET /app/api/transfer/export/<id>` needs `read`; `POST /app/api/transfer/import` needs `edit`, and any other method is answered with `405` (`reason` `method_not_allowed`, an `Allow: POST` header) before the payload is read.
- `/app/api/forms/...` and `/app/api/parameters/...` — form/parameter metadata for the block edit UI.
- `GET /app/api/versions/<layout_id>` — the draft and published versions of a layout (status, id, created, modified).
- `GET|POST /app/api/share/<layout_id>` — list or create share tokens for a layout; both need `edit`, since a token opens the layout to whoever holds it. Tokens are stored in the `explayouts_share` table, which the first `POST` creates when it is missing; a `GET` without the table answers `[]` and creates nothing.

## HTML form fragments

The block edit sidebar loads HTML fragments (not JSON), served by `app.php`:

```
GET /explayouts_ui_api/app/<locale>/blocks/<id>/edit   -> form_block_edit.tpl (wrapper, data-form URL)
GET /explayouts_ui_api/app/<locale>/blocks/<id>/form   -> form_block_fields.tpl (the <form>)
```

The form includes the `ezxform_token` hidden input and submits to `POST /explayouts_ui_api/app/api/<locale>/blocks/<id>`, which returns the updated block as JSON. A layout preview is available at `/explayouts_ui_api/app/preview/<layout_id>`.

## Standalone JSON module views

Besides the dispatcher, three simple read-only views exist:

```
/explayouts_ui_api/layouts/(LayoutID)/<id>
/explayouts_ui_api/rules/(RuleID)/<id>
/explayouts_ui_api/blocks/(ZoneID)/<id>
```

## Example curl session

Replace the placeholders with your own admin credentials — never commit real credentials.

```bash
BASE='https://example.com'
COOKIES=cookies.txt

# Get the CSRF token (inside an authenticated admin session)
curl -s -b "$COOKIES" "$BASE/explayouts_ui_api/app/api/config"

# Create a layout
curl -s -b "$COOKIES" -H "X-CSRF-Token: $TOKEN" -X POST \
  -d 'name=Home' -d 'layout_type=1_column' \
  "$BASE/explayouts_ui_api/app/api/layouts"

# Publish it
curl -s -b "$COOKIES" -H "X-CSRF-Token: $TOKEN" -X POST \
  "$BASE/explayouts_ui_api/app/api/layouts/2/publish"

# Create a block
curl -s -b "$COOKIES" -H "X-CSRF-Token: $TOKEN" -X POST \
  -d 'layout_id=2' -d 'zone_identifier=left' -d 'definition_identifier=text' \
  "$BASE/explayouts_ui_api/app/api/eng/blocks"

# Update a block
curl -s -b "$COOKIES" -H "X-CSRF-Token: $TOKEN" -X POST \
  -d 'name=Hello+Updated' -d 'view_type=default' -d 'parameters[content]=My+text' \
  "$BASE/explayouts_ui_api/app/api/eng/blocks/1"
```

`POST` bodies may be `application/x-www-form-urlencoded` or `application/json` — `requestData()` in `expLayoutsUIApplicationApi` falls back to `php://input` when `$_POST` is empty.

## Implementation notes

- `expLayoutsZone::fetchByLayout()` and `expLayoutsBlock::fetchByZone()` accept `null` for `$status` so the UI can read zones/blocks regardless of draft/published state.
- `expLayoutsCoreLayoutService::load()` loads by id only unless a status is passed.
- The dispatcher disables `eZDebug` output so JSON responses are never contaminated with HTML.
- An exception inside a handler is logged to `error.log` with message, file, line and trace, and answered with `500 {"error":"Internal error"}`. The `details` field (message, file and line) is added only when debug output is on for the request (the `app` view asks before it switches debug output off and passes the answer to `handle()`) (`site.ini` `[DebugSettings] DebugOutput`, with `DebugByIP`/`DebugByUser`): it carries server paths and database errors.

## Customization

### Settings layer (INI cascade)

This extension only ships `module.ini` and `design.ini` registrations. Everything the API exposes — layout types, zones, block definitions, view types, query types — is defined in `explayouts.ini` (owned by `explayouts`) and overridden through the normal INI cascade: extension defaults, `settings/siteaccess/<siteaccess>/`, siteaccess settings in active extensions, `settings/override/`. For example, adding a `[LayoutType_landing]` section in `settings/override/explayouts.ini.append.php` makes it appear in `GET /app/api/config/layout_types` with no code change.

Access control is via module policies. All views declare the `read` function on the `explayouts_ui_api` module, which is enough to open the editor and load its data. Every request that changes something — any method other than `GET`, `HEAD` and `OPTIONS` — also needs the `edit` function; without it the dispatcher answers `403` with `{"error":{"code":403,"reason":"access_denied",...}}` before any layout, block, rule, zone, share token or import is touched. Give editors `explayouts_ui_api/read` and `explayouts_ui_api/edit`, viewers `read` only — limit access with role policies rather than editing the module. Reading never writes for a user without `edit`: the zones a layout's type defines but the layout lacks, and the default collection of a block type that has one, are created on read only with `edit`; without it they are left out of the answer. The `403` and `405` answers carry the text on the top level as well (`"message"`), and the editor shows "You don't have permission to edit this layout." for a `403` with reason `access_denied` instead of its session timeout dialog.

### Template layer (design override cascade)

The HTML surfaces are ordinary design templates and can be overridden from another design extension by shipping the same relative path (the design cascade prefers siteaccess/admin designs over `standard`):

```
design/<yourdesign>/templates/explayouts_ui_api/app.tpl                (SPA bootstrap)
design/<yourdesign>/templates/explayouts_ui_api/form_create.tpl        (new layout form)
design/<yourdesign>/templates/explayouts_ui_api/form_block_edit.tpl    (block edit wrapper)
design/<yourdesign>/templates/explayouts_ui_api/form_block_fields.tpl  (block edit fields)
```

Overriding `app.tpl` is the supported way to swap or extend the loaded CSS/JS bundles.

### PHP layer (safe extension points)

- The JSON contract of `/app/api/...` is the public interface — build integrations against the endpoints, not against the dispatcher internals.
- New block parameters appear in the edit forms automatically once a block handler's `getParameters()` declares them; extend the UI by writing block handlers (see `explayouts` and `explayouts_standard`), not by patching `expLayoutsUIApplicationApi`.
- For custom read-only JSON views, follow the pattern of `modules/explayouts_ui_api/layouts.php`: a small module view over the `explayouts_core` services in your own extension.
