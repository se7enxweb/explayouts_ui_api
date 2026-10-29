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
- Policies - every view needs `explayouts_ui_api/read`; every changing request (any method but `GET`, `HEAD` and `OPTIONS`) also needs `explayouts_ui_api/edit` and is refused with a `403` JSON answer without it. An error's message, file and line reach the client only with debug output on; the log always has them.
- App shell module - `modules/explayouts_ui_api/app.php` serves the SPA shell, the API entry, the layout preview (`/explayouts_ui_api/app/preview/<layout_id>`) and the HTML block-edit form fragments.
- Bootstrap template - `design/standard/templates/explayouts_ui_api/app.tpl` renders the meta tags the JavaScript reads: `explayouts-route-prefix` (set to `/explayouts_ui_api`), `explayouts-base-path`, `ngcb-base-path` and `ezxform-token`.
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

- Exponential Layouts UI API is Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
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

Third-party code
----------------

Two parts of this extension come from Netgen Layouts and stay under its MIT License, whose notice ships with them:

- `design/standard/javascript/netgen-layouts.js`, the editor's application bundle, built from `netgen/layouts-ui`
  and patched for Exponential: notice in `design/standard/javascript/netgen-layouts-LICENSE.txt`, which also names
  the libraries the bundle still contains (Backbone, Underscore, Handlebars, NProgress, striptags, the content
  browser with React: MIT; source-map: BSD 3-Clause) with their notices.
- `design/standard/media/netgen-layouts.*`, the editor's icon font, from `netgen/layouts-core`: notice in
  `design/standard/media/netgen-layouts-LICENSE.txt`.

The editor's Material Icons font (`design/standard/media/MaterialIcons-Regular.*` and its copy in
`design/standard/fonts/`) comes from Google's `google/material-design-icons` and stays under the Apache License,
Version 2.0: notice in `design/standard/media/MaterialIcons-LICENSE.txt` and `design/standard/fonts/MaterialIcons-LICENSE.txt`.

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

Browser
- The editor page loads no jQuery and no jQuery UI (and nothing of `ezjscore`): the app bundle brings its own DOM
  layer, dialogs and drag and drop (see "The app bundle and its patches"). Scripts added through
  `explayouts_ui_api.ini [App] Javascripts[]` must not expect `window.jQuery`.

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
| `GET /app/api/versions/<layout_id>`, `GET|POST /app/api/share/<layout_id>` | Layout versions and share tokens (share tokens need `edit`) |
| `GET /app/<locale>/blocks/<id>/edit`, `GET /app/<locale>/blocks/<id>/form` | HTML block-edit form fragments |
| `GET /app/preview/<layout_id>` | Layout preview |

Creating a layout:

```
POST /explayouts_ui_api/app/api/layouts
{ "name": "My layout", "layout_type": "2_column" }
```

Modifying requests must carry the eZ form token, either as an `X-CSRF-Token` HTTP header or an `ezxform_token` form field; fetch the current token from `GET /app/api/config`. Use placeholder credentials in examples — never commit real credentials.

See [doc/USAGE.md](doc/USAGE.md) for exhaustive scenarios: every endpoint with request/response bodies, a complete curl session, the HTML form fragment flow, implementation notes, and the full customization guide covering the settings layer (INI cascade — everything the API exposes is defined in `explayouts.ini` and overridable without code changes; access control via module policies), the template layer (design override cascade for `app.tpl` and the form templates — the supported way to swap CSS/JS bundles) and the PHP layer (building against the JSON contract, adding block parameters via handlers, custom read-only JSON views).

The app bundle and its patches
------------------------------

`design/standard/javascript/netgen-layouts.js` is the built bundle of
[netgen/layouts-ui](https://github.com/netgen-layouts/layouts-ui) **2.0.0** (commit `e61f093`, file
`bundle/Resources/public/js/netgen-layouts.js`; the first import of this package was that file byte for byte), with
changes for Exponential made to the built bundle. The bundle is not rebuilt from the layouts-ui sources: their build
(Grunt, browserify, Node 14, node-sass) is not reproducible any more, and a rebuild would drop every change below.

What differs from the original is versioned in `src/app-patches/`:

- `manifest.json`: the original (version, commit, download address, sha256 of the bundle, of each of its 157
  modules and of the skeleton around them) and each patched module with what was changed and in which commit;
- `header.js`: what stands in front of the bundle (`nglT()`/`nglTh()` for the interface texts);
- `modules/NNN-name.js`: browserify module NNN's function exactly as the bundle carries it (49 of 157: the interface
  texts, the editor's own DOM layer in place of jQuery (module 140), its own drag and drop on Pointer Events in place
  of jQuery UI (modules 135-138; 134 is empty) and its keyboard mode (module 139), the view type select, the plain DOM
  code in place of jQuery plugins, and the others listed in the manifest).

The editor runs without jQuery. Module 140 (`jquery`; until 1.3.29 the page's jQuery 4 from ezjscore, before that
the jQuery 2.2.4 built in) is the editor's DOM layer: the part of jQuery the bundle calls, with jQuery's behaviour,
on plain DOM. Backbone and the app's modules reach it as `Backbone.$` and `Core.$`; it is not put on the page as
`window.jQuery` or `window.$`.

| `Core.$` | |
|---|---|
| `$( selector \| html \| element \| array \| fn )` | collections; `$( fn )` runs when the document is ready, after the current script |
| `each`, `map`, `get`, `toArray`, `eq`, `first`, `filter`, `index`, `find`, `closest`, `children`, `parent`, `parents`, `next`, `siblings` | traversal; a selector starting with `>` is relative, as in jQuery |
| `addClass`, `removeClass`, `hasClass`, `attr`, `removeAttr`, `prop`, `data`, `removeData`, `css`, `height`, `val` | `data()` reads `data-*` attributes as jQuery converts them; `data( '_view' )` is the element's `ngl_view`, where every Backbone view of the app is kept (plain code: `el.ngl_view`) |
| `html`, `text`, `append`, `after`, `replaceWith`, `remove`, `empty`, `show`, `hide`, `fadeIn`, `fadeOut` | what is taken out loses the layer's handlers and data (jQuery's cleanData); the scripts of HTML put in run once when they reach the document, as with jQuery; fades queue per element |
| `on`, `off`, `trigger`, `ajaxStart`, `ajaxSend`, `ajaxSuccess`, `ajaxError`, `ajaxComplete`, `ajaxStop` | one native listener per element and type; delegation, namespaces (Backbone's `.delegateEvents<cid>`), jQuery's event object (`currentTarget`, `delegateTarget`, `originalEvent`, `which`, `return false`); `trigger()` dispatches a bubbling `CustomEvent` (the arguments as `detail`) that `addEventListener` listeners get too; `focus`, `blur`, `click` call the element's own method |
| `serialize`, `serializeArray` | as jQuery |
| `$.ajax`, `$.get`, `$.active` | XMLHttpRequest with jQuery's options, jqXHR (a promise with `done`, `fail`, `always`, `then`, `responseJSON` ...), headers and encodings; the global events on the document drive the progress bar, the count of running writes (the warning on leaving), and the session timeout and permission dialogs. `javascript/explayouts-app.js` sends its requests through `Core.$.ajax` for that |
| `$.Deferred`, `$.when`, `$.param`, `$.extend`, `$.each`, `$.inArray` | as jQuery 3+ (`done` at once, `then` later) |

`tests/js/editor-without-jquery.test.mjs` runs the whole page without jQuery through the main flows against a fake of
the API, then the same flows with jQuery 4 in place of module 140 and compares every request and the document after
each flow; that run records what the bundle calls of jQuery, and the test fails if the layer lacks any of it.
`tests/js/dom-layer.test.mjs` tests the layer on its own, against jQuery 4 where it can.

Dialogs, collapsible panels, dropdown menus, tooltips, the date field and the inline switches of the sidebar run
without jQuery plugins. Bootstrap 3's JavaScript (modal, collapse, dropdown, transition), Moment.js with its locales
and eonasdan-bootstrap-datetimepicker are no longer in the bundle; their modules keep their ids and hold plain DOM
code (`Core.ui`, put together in `./extended/jquery_override`) or nothing:

| `Core.ui` | instead of | |
|---|---|---|
| `dialog.show( el, options )`, `hide( el )`, `is_shown( el )` | `$.fn.modal` | Bootstrap 3 markup and CSS; `backdrop`, `keyboard`, `on_shown`, `on_hidden`; Tab stays in the open dialog, the focus goes back on close; shown again while it fades out (or closed while it fades in) it turns round where it stands |
| `collapse.show/hide/toggle( el )` | `$.fn.collapse` | clicks on `[data-toggle="collapse"]` |
| `dropdown.toggle( toggle )`, `close_all()` | `$.fn.dropdown` | clicks and keys on `[data-toggle="dropdown"]`, opens upwards when there is no room below |
| `xeditable`, `dependable_select`, `master_slave_selects`, `view_types`, `video_thumb_fetcher`, `bm_tooltip` ( elements ) | the jQuery plugins of the same names | called by `reinitialize_plugins` (module `init`) |
| `show( el, ms )`, `hide( el, ms )`, `transition_end`, `find`, `elements`, `tabbables`, `fire` | jQuery's `show`/`hide`, `emulateTransitionEnd` | helpers |

The DOM events `show.bs.modal`, `hidden.bs.collapse`, `shown.bs.dropdown` ... are still fired (as DOM events: listen
with `addEventListener`). The content browser dialog of `javascript/explayouts-app.js` is a `Core.ui.dialog` too. The date field of `./views/datetime_picker` is the browser's `<input type="datetime-local">`
(it sends the same `YYYY-MM-DDTHH:mm` in local time); the template helper `date` formats with
`Intl.DateTimeFormat`.

After `npm ci` (the bundle is read with acorn, not evaluated), `node src/app-patches/apply.mjs` builds the bundle
from them (`--check` compares, `--from=<original bundle>` builds
from the layouts-ui file, `--diff` shows the changes against it, `--extract=<id>` copies a module out to change it;
details in the file). `tests/js/app-patches.test.mjs` checks that the bundle is the original plus exactly these
patches, without needing the original (with `LAYOUTS_UI_BUNDLE=<original bundle>` it also rebuilds from it,
`LAYOUTS_UI_BUNDLE=download` fetches the original from the address in the manifest and checks its sha256 first).

To change the bundle: `--extract` the module if it is not patched yet, edit its file, note the change in
`manifest.json` and run `apply.mjs`. The bundle is linked without a version query: after a deploy, a hard reload makes a
browser fetch it again.

Moving with the keyboard
------------------------

Everything the pointer moves can be moved with the keyboard as well (module 139, on the sortable lists and
draggables of modules 135 and 138). The grips are buttons: the handle in a block's header ("Move block ..."), a block
type of the toolbar ("Add block ..."), a collection item's handle ("Move item ...", manual items, and in a dynamic
collection the items pinned to a slot); a hint tells screen readers the keys.

| Key | |
|---|---|
| Space, Enter | pick up; picked up: drop where it is |
| Arrow up, Arrow down | one place up or down; at the first or last place of a zone into the zone before or after it |
| Home, End | the first or last place in the zone (in the collection) |
| Escape | puts everything back; so does leaving the grip (Tab, a click elsewhere) |
| Alt+Arrow up, Alt+Arrow down | (not picked up) one place at once, saved at once |

What is picked up moves itself (outlined) and keeps the focus. Zones the pointer cannot drop into are skipped and
said: linked zones, zones that do not take the block type or are inherited, a container block's placeholders for a
container block. A block type picked up from the toolbar starts at the first place of the first zone that takes it;
dropped, it becomes a new block there, and Escape returns it to the toolbar (and the focus to the toolbar's button).
Each step is said in one polite live region ("Zone Main, position 2 of 3."), with the texts of `spa_strings.tpl`.
After a drop the grip of the block or item keeps the focus, also when the editor draws it again, for a few seconds and
only until the user presses, types or focuses anywhere else. The page scrolls to
follow, smoothly unless the browser asks for reduced motion. *Set position* in an item's menu stays as it was.

A keyboard move runs through the same callbacks as a pointer drag (`keyStart`, `keyMove`, `keyDrop`, `keyCancel` of a
sortable list; `keyStart`, `keyOver`, `keyDrop` of a draggable), so it is saved with the same requests.
`tests/js/keyboard-move.test.mjs` makes each move both ways and compares the calls.

Leaving the editor
------------------

The editor saves every change to the draft at once (sidebar fields on change and 500 ms after the last key, inline
texts and the rich text and code editors 500 ms after the last change). Leaving the page therefore asks "There could
be unsaved changes" only when something can still be lost (`Core.has_unsaved_changes()`, module `init`):

- a request other than GET is running (a save, a move, a new block; a form without a `method` attribute is sent as
  POST, as the API expects);
- a block's inline text was typed in, pasted into or otherwise changed within the last second (its save has not
  started yet; an inline text emptied completely is saved too, netgen/layouts-ui skipped an empty value);
- a form in the sidebar or in a dialog holds other values than when it was loaded or last saved (`save_via_form`
  keeps the state it sent once saved, the form token does not count); that includes the hidden fields the inline
  texts and the rich text and code editors write into, also after their save failed.

Publish, discard, close and the dialog's Cancel leave without asking, as before. netgen/layouts-ui asked on every
other leave, also right after opening a layout. Not covered: the collection type selector of the sidebar with its own
Apply button (not a form), and a sidebar form whose save the server refused: the editor replaces that form with the
answer and shows its error dialog.
`tests/js/unsaved-changes.test.mjs` runs both modules from the bundle.

Rich text and code editors
--------------------------

The app bundle (`design/standard/javascript/netgen-layouts.js`) was written against CKEditor 4 (rich text blocks) and the
Ace editor (HTML snippet and Markdown blocks). Neither is shipped any more: CKEditor 4 is end of life without free
security updates. `design/standard/javascript/explayouts-tiptap.js`, loaded by `app.tpl` before the app bundle, provides
`window.CKEDITOR` and `window.ace` with exactly the calls the bundle makes of them, backed by [Tiptap](https://tiptap.dev):

- Rich text: Tiptap with the toolbar CKEditor had there (undo/redo, paragraph format with heading 1-3 and
  preformatted, bold, italic, underline, strikethrough, remove formatting, link with "open in a new window", bulleted
  and numbered list with indent, block quote, alignment, horizontal line, table). Reads and writes HTML. While the
  content is unchanged (also after undoing every change) the stored HTML is returned exactly as it was. Once it
  changed, it is written as CKEditor wrote it (`src/editors/ckeditor-html.js`): `<li>a</li>` and `<td>x</td>` without
  an inner `<p>`, `<pre>` without `<code>`, tables without a `<colgroup>` or min-width, alignment as
  `style="text-align:center"` (the justify plugin's default; no `justifyClasses` were configured), `class` and
  `style` on paragraphs, headings, lists, list items and table parts and `border`/`cellpadding`/`cellspacing` on
  tables kept. An empty editor gives an empty string. The link form completes `example.com/x` to
  `https://example.com/x` (only when the start looks like a host: `www.…`, `localhost`, `host:port` or a name whose
  last label is not a file extension, so `page.html` stays relative) and `x@y.de` to `mailto:x@y.de`, keeps `/…`,
  `#…`, `mailto:` and `tel:` as typed, and refuses addresses such as `javascript:` or `data:` with a message. Kept
  `style` attributes are limited to text-align, width, height, border, padding, vertical-align, color,
  background-color, font-weight, font-style, text-decoration, list-style-type and margin-left, without values
  containing `url(`, `expression`, `javascript:`, `behavior`, `-moz-binding` or `@import`.
- Code: one Tiptap code block with syntax highlighting (lowlight/highlight.js: HTML, Markdown). The value is the plain
  text, byte for byte. Tab and Shift+Tab indent and outdent with spaces, Enter keeps the indentation, Ctrl+S / Cmd+S
  saves as before, Escape moves the focus from the text to the editor element.

Both run with Tiptap's `injectCSS: false`: its base rules are in the stylesheet, so no `<style>` element is added
(`style-src` without `'unsafe-inline'`). The `style` attributes of the content itself (alignment, table widths) are
still written into the editor's DOM; under an enforced `style-src` without `'unsafe-inline'` the browser would not
show them while editing, though they are kept in the value.

Still normalised once the content changes: `<div>` and `<span>` are not in the schema, so their text is kept but the
elements (and their class or style) are not (`<div class="box"><span style="color:red">x</span></div>` becomes
`<p>x</p>`); `<b>`, `<i>`, `<del>`, `<strike>` become `<strong>`, `<em>`, `<s>`; attributes other than those listed
above (for example `id`, `title` on blocks, `scope` on table headers), the `<thead>` element (its rows stay, in `<tbody>`) and images are dropped, and a `<caption>` becomes a first table row.
Not carried over from CKEditor: anchors, bidi buttons, indentation of paragraphs outside lists, paste-from-Word
cleanup. From Ace: syntax checking (the bundle only filtered its DOCTYPE warning), themes, search box, code folding.

Sources are in `src/editors/`, styles in `design/standard/stylesheets/explayouts-tiptap.css` (admin4's `--a4-*`
tokens, light and dark mode, when admin4's stylesheet is on the page; fallbacks otherwise), interface texts in `spa_strings.tpl` (context `design/standard/explayouts_ui_api/spa`). Build
(Node 24, exact versions from `package-lock.json`; the result is byte-identical for the same lock file):

```bash
cd extension/explayouts_ui_api
npm ci
npm run build     # writes design/standard/javascript/explayouts-tiptap.js
npm test          # node --test tests/js/*.test.mjs (happy-dom)
```

`node_modules/` is not committed. The bundles are linked without a version query; a hard reload fetches a rebuilt one.

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
