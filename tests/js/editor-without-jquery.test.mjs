/**
 * The whole layout editor without jQuery: app.tpl's scripts (tests/js/support/editor-page.mjs) against a fake of the
 * API (support/fake-api.mjs), driven through the main flows (support/editor-flows.mjs): open a layout, select and edit
 * a block in the sidebar and in place, move it to another zone, drag a block type from the toolbar into a zone, add
 * items to a collection through the content browser dialog, delete a block from its menu, move a block with the
 * keyboard, cancel Discard, publish.
 *
 * The same flows then run once more with the page's jQuery 4 in place of the bundle's DOM layer (module jquery, as
 * until 1.3.29): every request, every observation and the whole document after each flow must be the same. That run
 * also records what the bundle calls of jQuery, and every one of those methods has to be in the DOM layer.
 *
 * Run: npm ci && npm test (in extension/explayouts_ui_api)
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GPL-2.0-or-later
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { execFile } from 'node:child_process';
import { root, BASE, TOKEN } from './support/editor-page.mjs';
import { FLOWS } from './support/editor-flows.mjs';
import { GlobalRegistrator } from '@happy-dom/global-registrator';
import { parseBundle } from '../../src/app-patches/bundle.mjs';

/** Every flow on a fresh page, in a process of its own (support/run-flows.mjs): what each saw, the document after it. */
function runFlows( mode ) {
    return new Promise( ( done, fail ) => {
        execFile( process.execPath, [ resolve( root, 'tests/js/support/run-flows.mjs' ) ].concat( mode ? [ mode ] : [] ),
                  { maxBuffer: 64 * 1024 * 1024 }, ( err, stdout, stderr ) => {
            const at = stdout.indexOf( '@@FLOWS@@' );
            if ( err || at < 0 ) return fail( new Error( 'run-flows ' + ( mode || '' ) + ': ' + ( err ? err.message : '' ) + stderr.slice( -2000 ) ) );
            done( JSON.parse( stdout.slice( at + 9 ) ) );
        } );
    } );
}

/** The DOM layer, module jquery of the bundle (with Core.ui's helpers, which it uses for show and hide). */
function layer() {
    const bundle = parseBundle( readFileSync( resolve( root, 'design/standard/javascript/netgen-layouts.js' ), 'utf8' ) );
    const load = ( id, deps = {} ) => {
        const module = { exports: {} };
        ( 0, eval )( '(' + bundle.modules.get( id ).source + ')' ).call( module.exports, ( d ) => deps[ d ], module, module.exports );
        return module.exports;
    };
    return load( '140', { 101: load( '101' ) } );
}

// a document for checking selectors
before( () => GlobalRegistrator.register() );
after( () => GlobalRegistrator.unregister() );

const P = BASE + '/app/api';
let without;

test( 'without jQuery: the page has none, and every flow does what the editor did', async () => {
    without = await runFlows();
    const s = without;
    assert.deepEqual( s.jQueryOnPage, [ 'undefined', 'undefined' ], 'neither window.jQuery nor window.$' );
    assert.ok( s.layer.includes( 'ajax' ) && s.layer.includes( 'Deferred' ), 'Core.$ is the DOM layer' );
    assert.deepEqual( s.errors, [] );
    assert.deepEqual( s.rejections, [] );
    // the Layouts dashboard's "new layout" form, asked for when the editor leaves after publish
    assert.deepEqual( s.unknownRequests.filter( ( r ) => r !== 'GET /app/layouts/form/create' ), [] );

    // opening: the app's requests and what it draws
    assert.deepEqual( s.openLayout.gets, [ P + '/config', P + '/layouts/shared', P + '/layouts/1?published=false&fallback=published',
        P + '/config/layout_types', P + '/config/block_types?layout=1', P + '/eng/layouts/1/blocks' ] );
    assert.deepEqual( s.openLayout.blocks, [ 'top: Welcome', 'main: News' ] );
    assert.equal( s.openLayout.types.length, 3 );
    assert.equal( s.openLayout.title, 'Home page - Exponential Layouts' );

    // a block selected: its sidebar with tabs and forms; typing and changing save the form
    const edit = s.editBlock;
    assert.deepEqual( edit.sidebar.editing, [ 'title' ] );
    assert.deepEqual( edit.sidebar.tabs, [ 'tab-content true 0', 'tab-design false -1' ] );
    assert.deepEqual( edit.sidebar.forms, [ 'content_edit', 'design_edit' ] );
    assert.equal( edit.sidebar.loader, 'none', 'the loader faded out' );
    assert.deepEqual( edit.loaded, [ BASE + '/app/eng/blocks/10/edit', BASE + '/app/eng/blocks/10/form/edit/content', BASE + '/app/eng/blocks/10/form' ] );
    assert.deepEqual( edit.tabAfterKey, [ 'tab-design', [ 'tab-content-tab tab-pane none', 'tab-design-tab tab-pane active ' ] ] );
    assert.deepEqual( edit.saved, [
        { request: 'POST ' + P + '/eng/blocks/10', body: { 'parameters[title]': 'Hello world' }, token: TOKEN },
        { request: 'POST ' + P + '/eng/blocks/10', body: { name: 'Greeting', view_type: 'title' }, token: TOKEN } ] );
    assert.deepEqual( edit.blocks, [ 'top: Greeting', 'main: News' ], 'the block drawn again from the answer' );

    // typed in place: into the sidebar's (hidden) field, then saved
    assert.deepEqual( s.inlineEdit.state.slice( 0, 1 ), [ 'true' ], 'contenteditable while selected' );
    assert.equal( s.inlineEdit.state[ 2 ], true, 'the field edited in place is hidden in the sidebar' );
    assert.equal( s.inlineEdit.field, 'Typed in place' );
    assert.deepEqual( s.inlineEdit.saved, [ { request: 'POST ' + P + '/eng/blocks/10', body: { 'parameters[title]': 'Typed in place' }, token: TOKEN } ] );

    // moved to the main zone, below the list
    assert.deepEqual( s.moveBlock.saved, [ { request: 'POST ' + P + '/eng/blocks/10/move/zone',
        body: { zone_identifier: 'main', parent_position: 1, layout_id: 1 }, token: 'api-token' } ] );
    assert.deepEqual( s.moveBlock.blocks, [ 'top: ', 'main: News, Greeting' ] );
    assert.equal( s.moveBlock.sorting, false );

    // a block type from the toolbar: created there, selected, the toolbar keeps its types, the panel closes
    const add = s.addBlockFromToolbar;
    assert.deepEqual( add.open, [ 'true', '' ] );
    assert.deepEqual( add.closed, [ 'false', 'none' ] );
    assert.deepEqual( add.saved, [ { request: 'POST ' + P + '/eng/blocks', body: { definition_identifier: 'text', block_type: 'text',
        zone_identifier: 'main', layout_id: '1', parent_position: 0, parent_block_id: null }, token: 'api-token' } ] );
    assert.deepEqual( add.blocks, [ 'top: ', 'main: , News, Greeting' ] );
    assert.deepEqual( add.toolbar, [ 'title', 'text', 'list' ] );
    assert.deepEqual( add.editing, [ 'text' ] );

    // the content browser dialog adds an item to the collection, which is loaded again
    const items = s.addCollectionItems;
    assert.equal( items.listed.length, 2 );
    assert.deepEqual( items.rows, [ 'Folder', 'Third article' ] );
    assert.deepEqual( items.saved, [ { request: 'POST ' + P + '/eng/blocks/20/collections/default/items',
        body: { items: [ { value: 70, value_type: 'ez_location', item_type: 'manual', position: 0 } ] }, token: 'api-token' } ] );
    assert.deepEqual( items.loaded, [ P + '/content_browser?parent_node_id=2&search=&offset=0&limit=50', P + '/eng/blocks/20/collections/default/result' ] );
    assert.match( items.items[ 0 ], /Third article/ );
    assert.deepEqual( items.dialog, [ 'modal fade', 'none', false ], 'the dialog closed' );
    // "+ Add items" is a button (focusable, Enter and Space press it), and the focus is back on it after the dialog
    assert.deepEqual( items.trigger, [ 'BUTTON', 'button', 0 ] );
    assert.equal( items.focus, true, 'the focus went back to "+ Add items"' );

    // delete from the block's menu: the menu opens (aria-expanded follows), the dialog asks
    assert.deepEqual( s.deleteBlock.menu, [ true, 'true' ] );
    assert.deepEqual( s.deleteBlock.asked, [ 'Delete block', 'mymodal modal fade in', 'true' ] );
    assert.deepEqual( s.deleteBlock.saved, [ { request: 'DELETE ' + P + '/eng/blocks/20', body: '', token: 'api-token' } ] );
    assert.deepEqual( s.deleteBlock.blocks, [ 'top: ', 'main: , Greeting' ] );
    assert.equal( s.deleteBlock.open, false );

    // the title block moved one place up with the keyboard: said at each step, saved as a move with the pointer is
    const kbd = s.moveBlockByKeyboard;
    assert.deepEqual( kbd.name, [ 'button', 'Move block Greeting', 0 ] );
    assert.deepEqual( kbd.picked, [ 'Picked up Greeting. Zone main, position 2 of 2. Use the arrow keys to move, Space to drop, Escape to cancel.', 'true' ] );
    assert.deepEqual( kbd.moved, [ 'Zone main, position 1 of 2.', [ 'top: ', 'main: Greeting, ' ] ] );
    assert.equal( kbd.dropped, 'Dropped Greeting in zone main at position 1.' );
    assert.deepEqual( kbd.saved, [ { request: 'POST ' + P + '/eng/blocks/10/move/zone',
        body: { zone_identifier: 'main', parent_position: 0, layout_id: 1 }, token: 'api-token' } ] );
    assert.deepEqual( kbd.blocks, [ 'top: ', 'main: Greeting, ' ] );
    assert.deepEqual( [ kbd.focus, kbd.pressed ], [ 'title', 0 ], 'the grip of the block moved keeps the focus' );

    assert.deepEqual( s.discardCancelled, { asked: 'Discard changes', saved: [], modals: 0 } );

    // publish from the header: its menu, then the request after the app's wait, then the editor leaves
    assert.deepEqual( s.publish.menu, [ true, 'true' ] );
    assert.equal( s.publish.closed, false, 'a click beside it closes the menu' );
    assert.deepEqual( s.publish.saved.map( ( r ) => r.request ), [ 'POST ' + P + '/layouts/1/publish' ] );
    assert.equal( s.publish.leaving, true );
} );

let withJQuery;

test( 'with jQuery 4 in place of the DOM layer every flow gives the same requests, observations and document', async () => {
    assert.ok( without, 'the run without jQuery' );
    withJQuery = await runFlows( 'jquery' );
    assert.deepEqual( withJQuery.jQueryOnPage, [ 'function', 'undefined' ] );
    for ( const name of FLOWS ) {
        assert.equal( JSON.stringify( withJQuery[ name ], null, 1 ), JSON.stringify( without[ name ], null, 1 ), name );
        assert.ok( withJQuery[ name + ' (document)' ] === without[ name + ' (document)' ], name + ': the document' );
    }
    assert.deepEqual( withJQuery.unknownRequests, without.unknownRequests );
    assert.deepEqual( [ withJQuery.errors, withJQuery.rejections ], [ [], [] ] );
} );

test( 'every jQuery method and property the bundle used in the flows is in the DOM layer', () => {
    assert.ok( withJQuery, 'the run with jQuery' );
    const $ = layer();
    // what ./extended/jquery_override adds to the layer as the app starts: its methods and Core.ui (ngl_ui)
    const override = readFileSync( resolve( root, 'src/app-patches/modules/017-extended-jquery-override.js' ), 'utf8' );
    const added = [ ...override.matchAll( /\br\.(fn\.)?(\w+)=/g ) ].map( ( m ) => ( m[ 1 ] ? 'fn.' : '$.' ) + m[ 2 ] );
    assert.ok( added.includes( 'fn.ajax_submit' ) && added.includes( '$.ngl_ui' ), added.join( ' ' ) );
    const missing = withJQuery.used.filter( ( name ) => {
        if ( added.includes( name.replace( / \((read|set)\)$/, '' ) ) ) return false;
        const m = /^(fn|\$)\.(\w+)/.exec( name );
        if ( name === '$()' ) return typeof $ !== 'function';
        return !m || ( m[ 1 ] === 'fn' ? typeof $.fn[ m[ 2 ] ] !== 'function' : !( m[ 2 ] in $ ) );
    } );
    assert.deepEqual( missing, [] );
    // the inventory these flows reach; a new one shows up here and, if the layer lacks it, in `missing` above
    assert.ok( withJQuery.used.length >= 45, withJQuery.used.join( ' ' ) );
    // the selectors the bundle hands the layer are CSS (jQuery's own :first, :selected ... are gone)
    for ( const selector of withJQuery.selectors.filter( Boolean ) ) {
        assert.doesNotThrow( () => document.createDocumentFragment().querySelectorAll( selector.replace( /^\s*([>+~])/, ':scope $1' ) ), selector );
    }
} );

test( 'what the bundle calls of jQuery and the flows do not reach is in the DOM layer, or dead code', () => {
    const $ = layer();
    // From a search of the app's modules (1-95) for jQuery's names on its aliases: called on paths the flows above do
    // not take (dialog forms, collection items, the layout name, errors)
    for ( const name of [ 'next', 'siblings', 'prop', 'val', 'text', 'replaceWith', 'height', 'removeData', 'serializeArray' ] ) {
        assert.equal( typeof $.fn[ name ], 'function', name );
    }
    for ( const name of [ 'ajax', 'get', 'when', 'Deferred', 'param', 'extend', 'each', 'inArray' ] ) {
        assert.equal( typeof $[ name ], 'function', name );
    }
    // Dead, and not in the layer: ./extended/view's live_updates (no view sets `live`) call number_animate, typed and
    // animate, ./helpers' highlight (used by no template) animate and delay. None of them is jQuery's but animate
    // and delay, and no template or view reaches them.
    const bundle = readFileSync( resolve( root, 'design/standard/javascript/netgen-layouts.js' ), 'utf8' );
    assert.doesNotMatch( bundle, /\blive:\s*\{/, 'a view with live bindings' );
    assert.doesNotMatch( bundle, /,"highlight"\)|\.highlight\b/, 'a template using the highlight helper' );
    for ( const name of [ 'animate', 'delay', 'number_animate', 'typed' ] ) assert.equal( $.fn[ name ], undefined, name );
} );

test( 'the page scripts use no jQuery', () => {
    for ( const name of [ 'explayouts-app-head.js', 'explayouts-app.js', 'explayouts-mobile.js' ] ) {
        const code = readFileSync( resolve( root, 'design/standard/javascript', name ), 'utf8' ).replace( /\/\*[\s\S]*?\*\/|\/\/.*$/gm, '' );
        assert.doesNotMatch( code, /jQuery|(?<![\w.])\$[(.]|\.modal\(/, name );
    }
} );
