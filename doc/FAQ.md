# explayouts_ui_api FAQ

## How does this differ from netgen/layouts-ui?

`netgen/layouts-ui` is the JavaScript admin app plus a Symfony backend (controllers, serializers, forms). This extension keeps the app experience but re-implements the backend as a single legacy module (`app.php`) and a JSON dispatcher class (`expLayoutsUIApplicationApi`) built on the `explayouts_core` services — no Symfony, no REST bundle.

## Which database tables does it own?

One: `explayouts_share` (layout share tokens), created on demand by the share endpoint. All other data lives in the `explayouts_*` tables owned by the `explayouts` extension.

## How is the API authenticated?

Requests run in the normal authenticated eZ Publish admin session (cookies). Modifying requests additionally require the eZ form token from the `ezformtoken` extension, sent as an `X-CSRF-Token` header or `ezxform_token` form field. Fetch the current token from `GET /app/api/config`.

## Why do my POSTs return a CSRF error?

Either the session is not authenticated or the form token is missing/stale. Re-read the token from `/app/api/config` and re-send it; tokens are session-bound.

## Where do the layout types and block types in the app come from?

From `explayouts.ini` (`LayoutType_*`, `BlockSettings`/`BlockDefinition_*` sections) of the `explayouts` extension. INI overrides appear in the app immediately after a cache clear — no code changes needed.

## Can I use the API from outside the admin UI?

Yes, any HTTP client works as long as it carries an authenticated admin session cookie and the form token for modifying requests. See the curl session in doc/USAGE.md.
