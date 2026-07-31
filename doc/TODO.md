# explayouts_ui_api TODO

- The `explayouts_share` table is created ad hoc (`CREATE TABLE IF NOT EXISTS`, MySQL-specific DDL) inside `handleShare()`; move it to the installed schema in `explayouts/sql/` with per-driver variants.
- `expLayoutsUIApplicationApi` is a single ~1200-line dispatcher; splitting handlers into per-resource classes would ease maintenance.
- Module views declare only the `read` policy function; modifying endpoints rely on the form token and session — add an `edit`-level policy check for write operations.
- The SPA meta tags and asset names retain the upstream `nglayouts`/`ngcb` prefixes; renaming would need coordinated changes in the bundled JS.
- Upstream features not (fully) ported: undo/redo history, layout restore from archive, per-translation block parameters.
- No automated tests for the JSON endpoints.
