/**
 * Opens a layout in the editor (./editor-page.mjs, the whole app) in a process of its own and prints what went over
 * the wire as JSON, for tests/js/draft-once.test.mjs. The server is ./fake-api.mjs with layout 1 in the state the
 * scenario names:
 *
 *   published   the layout has no draft: the read answers with the published layout (fallback=published), the draft
 *               request creates the draft, reads after it answer with the draft
 *   stale       as published, but the two reads after the draft request still answer with the published layout, as a
 *               browser did with its earlier copy when the server answered its revalidation with 304
 *   draft       the layout has a draft already (a second tab, a quick reopen): reads answer with the draft
 *
 *   node open-layout.mjs <scenario> [hash, default #layout/1/edit]
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GPL-2.0-or-later
 */
import { openEditor, BASE } from './editor-page.mjs';
import { createApi } from './fake-api.mjs';

const scenario = process.argv[ 2 ] || 'published';
const hash = process.argv[ 3 ] || '#layout/1/edit';
const rejections = [];
process.on( 'unhandledRejection', ( reason ) => rejections.push( String( reason && reason.stack || reason ) ) );

const fake = createApi();
fake.state.published = scenario !== 'draft';
const published = () => fake( { method: 'GET', path: BASE + '/app/api/layouts/1', url: '', query: {}, headers: {}, body: '' } );
const firstRead = scenario === 'stale' ? published() : null;
let staleReads = scenario === 'stale' ? 2 : 0;
let drafts = 0;

function api( request ) {
    const path = request.path.slice( BASE.length );
    if ( request.method === 'POST' && path === '/app/api/layouts/1/draft' ) {
        drafts++;
        // the server creates the draft once and answers every later request with that draft
        fake.state.published = false;
        return { status: 201, body: fake( request ).body };
    }
    if ( request.method === 'GET' && path === '/app/api/layouts/1' && drafts > 0 && staleReads > 0 ) {
        staleReads--;
        return firstRead;
    }
    return fake( request );
}

// a hard stop: an editor that keeps asking for the draft never settles
const stop = setTimeout( () => finish( 'did not settle' ), 20000 );
let page;
try {
    page = await openEditor( { api, hash } );
    await page.settle();
    finish( null );
} catch ( e ) {
    finish( String( e && e.stack || e ) );
}

function finish( failure ) {
    clearTimeout( stop );
    const requests = page ? page.requests : [];
    const seen = {
        failure,
        draftRequests: requests.filter( ( r ) => /\/layouts\/1\/draft$/.test( r.path ) ).map( ( r ) => r.method + ' ' + r.path.slice( BASE.length ) ),
        layoutReads: requests.filter( ( r ) => r.method === 'GET' && /\/api\/layouts\/1$/.test( r.path ) ).length,
        blockReads: requests.filter( ( r ) => r.method === 'GET' && /\/api\/eng\/layouts\/1\/blocks$/.test( r.path ) ).length,
        hash: typeof window !== 'undefined' ? window.location.hash : null,
        layout: page && window.Core && window.Core.g.layout
            ? { published: window.Core.g.layout.get( 'published' ), status: window.Core.g.layout.get( 'status' ) } : null,
        blocks: typeof document !== 'undefined' ? document.querySelectorAll( '[data-zone] [data-block]' ).length : 0,
        unknownRequests: fake.unknown,
        errors: page ? page.errors.map( String ) : [],
        rejections
    };
    process.stdout.write( '\n@@OPEN@@' + JSON.stringify( seen ) + '\n', () => process.exit( 0 ) );
}
