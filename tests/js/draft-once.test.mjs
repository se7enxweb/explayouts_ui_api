/**
 * Opening a layout sends one draft request (POST layouts/:id/draft, create_new_draft) at most: modules ./models/layout
 * and ./application of design/standard/javascript/netgen-layouts.js (patches in src/app-patches/modules/). After the
 * draft request the editor routes to the edit view, and that route read the layout again. When the read still
 * answered with the published layout (the browser's earlier copy: the server answered its revalidation with 304 within
 * the same second), the editor asked for the draft once more, three to six times per opening on the live site. The
 * page now keeps the draft the request answered with.
 *
 * Each opening runs the whole app on a fresh page (support/open-layout.mjs) in a process of its own.
 *
 * Run: npm ci && npm test (in extension/explayouts_ui_api)
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GPL-2.0-or-later
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { resolve } from 'node:path';
import { execFile } from 'node:child_process';
import { root } from './support/editor-page.mjs';

function open( scenario, hash ) {
    return new Promise( ( done, fail ) => {
        execFile( process.execPath, [ resolve( root, 'tests/js/support/open-layout.mjs' ), scenario ].concat( hash ? [ hash ] : [] ),
                  { maxBuffer: 16 * 1024 * 1024 }, ( err, stdout, stderr ) => {
            const at = stdout.indexOf( '@@OPEN@@' );
            if ( err || at < 0 ) return fail( new Error( 'open-layout ' + scenario + ': ' + ( err ? err.message : '' ) + stderr.slice( -2000 ) ) );
            done( JSON.parse( stdout.slice( at + 8 ) ) );
        } );
    } );
}

function opened( s, what ) {
    assert.equal( s.failure, null, what + ': the page settles' );
    assert.equal( s.hash, '#layout/1/edit', what + ': the editor is on the edit view' );
    assert.deepEqual( s.layout, { published: false, status: 1 }, what + ': the editor holds the draft' );
    assert.ok( s.blocks > 0, what + ': the blocks are shown' );
    assert.equal( s.blockReads, 1, what + ': the blocks are read once' );
    assert.deepEqual( s.unknownRequests, [], what + ': no request the server does not know' );
    assert.deepEqual( s.errors, [], what + ': no error' );
    assert.deepEqual( s.rejections, [], what + ': no unhandled rejection' );
}

test( 'a layout without a draft: one draft request, and the layout is read once', async () => {
    const s = await open( 'published' );
    opened( s, 'published' );
    assert.deepEqual( s.draftRequests, [ 'POST /app/api/layouts/1/draft' ] );
    assert.equal( s.layoutReads, 1 );
} );

test( 'a read that still answers with the published layout after the draft request does not ask for the draft again', async () => {
    const s = await open( 'stale' );
    opened( s, 'stale' );
    assert.deepEqual( s.draftRequests, [ 'POST /app/api/layouts/1/draft' ] );
} );

test( 'a layout with a draft (a second tab, a quick reopen): no draft request, the draft is edited', async () => {
    const s = await open( 'draft' );
    opened( s, 'draft' );
    assert.deepEqual( s.draftRequests, [] );
    assert.equal( s.layoutReads, 1 );
} );
