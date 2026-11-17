/**
 * Changing only the View type on the Design tab saves the block (modules ./lib/jquery/view_types,
 * ./lib/jquery/master_slave_selects and ./form of design/standard/javascript/netgen-layouts.js; patches in
 * src/app-patches/modules/). The view type select carries js-skip-on-change, so the form's own change handler skips
 * it and view_types submits the form itself. A list's View type is the master of the Item view type: its change was
 * saved only through the change master_slave_selects triggers on the item view type, and that change is not
 * triggered when the new view type leaves no item view type to choose (list_zigzag, list_accordion, a view type of
 * your own), so the block kept its old view type until another field changed.
 *
 * The modules run from the bundle itself, on its DOM layer (module jquery, as on the page); the form view's $submit,
 * should_skip and params_has_changed are its own, bound to the form as its events are.
 *
 * Run: npm ci && npm test (in extension/explayouts_ui_api)
 *
 * @license GPL-2.0-or-later
 */
import { test, before, after, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { GlobalRegistrator } from '@happy-dom/global-registrator';
import { parseBundle } from '../../src/app-patches/bundle.mjs';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '../..' );
const parsed = parseBundle( readFileSync( resolve( root, 'design/standard/javascript/netgen-layouts.js' ), 'utf8' ) );

let dom;
let Form;
let core;
let saves;

function idOf( name ) {
    for ( const m of parsed.modules.values() ) if ( name in m.deps ) return String( m.deps[ name ] );
    throw new Error( 'no module ' + name );
}

function load( id, standIns ) {
    const m = parsed.modules.get( id );
    const module = { exports: {} };
    const fn = ( 0, eval )( '(' + m.source + ')' );
    fn.call( module.exports, ( name ) => {
        if ( name in standIns ) return standIns[ name ];
        throw new Error( 'module ' + id + ' asks for ' + name );
    }, module, module.exports );
    return module.exports;
}

const wait = ( ms ) => new Promise( ( done ) => setTimeout( done, ms ) );

before( async () => {
    GlobalRegistrator.register( { url: 'https://example.test/exponential_admin/explayouts_ui_api/app#layout/1/edit' } );
    // Core.ui: the helpers of bootstrap/js/transition, which ./lib/jquery/* add to; Core.$: the app's DOM layer
    const ui = load( idOf( 'bootstrap/js/transition' ), {} );
    dom = load( idOf( 'jquery' ), { 101: ui } );
    core = { $: dom, View: { extend: ( proto ) => proto }, ui };
    load( idOf( './lib/jquery/master_slave_selects' ), { '../../core': core } );
    load( idOf( './lib/jquery/view_types' ), { '../../core': core } );
    Form = load( idOf( './form' ), { '../core': core, underscore: { debounce: ( fn ) => fn } } );
} );

after( async () => {
    await GlobalRegistrator.unregister();
} );

/** The Design tab of a list block (form_block_fields.tpl), bound to the form view's own handlers. */
beforeEach( () => {
    document.body.innerHTML =
        '<div class="layouts-form"><form method="post" class="block-form" name="design_edit">' +
        '<div class="master-slave-selects">' +
        '<div class="row-input"><select id="vt" name="view_type" class="master view-type js-skip-on-change">' +
        '<option value="list" selected>list</option><option value="grid">grid</option><option value="list_zigzag">list_zigzag</option></select></div>' +
        '<div class="row-input"><select id="ivt" name="item_view_type" class="slave">' +
        '<option value="standard" data-master="list,list_numbered,grid,grid_featured" selected>standard</option>' +
        '<option value="line" data-master="list,list_numbered">line</option>' +
        '<option value="zigzag" data-master="list_zigzag_only">zigzag</option></select></div>' +
        '</div>' +
        '<div class="row-input"><select id="cols" name="columns" class="view-type js-skip-on-change"><option value="2" selected>2</option><option value="3">3</option></select></div>' +
        '</form></div>';
    saves = [];
    const form = dom( 'form' );
    const view = Object.assign( Object.create( Form ), {
        $: ( selector ) => form.find( selector ),
        is_query_form: false,
        form_id: 'design_edit',
        model: { save_via_form: () => saves.push( dom( 'form' ).serialize() ) }
    } );
    // events: { "submit form": "$submit", change: "$submit", ... } of the form view
    dom( '.layouts-form' ).on( 'submit', 'form', ( e ) => view.$submit( e ) ).on( 'change', ( e ) => view.$submit( e ) );
    core.ui.master_slave_selects( document.querySelectorAll( '.master-slave-selects .master' ) );
} );

/** As the user does: a new value, then the browser's change event. */
function choose( id, value ) {
    const select = document.getElementById( id );
    select.value = value;
    select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
}

test( 'a plain view type select saves its change', async () => {
    choose( 'cols', '3' );
    await wait( 20 );
    assert.equal( saves.length, 1 );
    assert.match( saves[ 0 ], /columns=3/ );
} );

test( 'the master select saves its change once when an item view type stays to choose', async () => {
    choose( 'vt', 'grid' );
    await wait( 20 );
    assert.equal( saves.length, 1, 'saved once (on the page the item view type change saves it first; the second submit sends the same values and is skipped)' );
    assert.match( saves[ 0 ], /view_type=grid/ );
} );

test( 'the master select saves its change when the new view type leaves no item view type', async () => {
    choose( 'vt', 'list_zigzag' );
    await wait( 20 );
    assert.equal( saves.length, 1, 'the change is saved without another edit' );
    assert.match( saves[ 0 ], /(^|&)view_type=list_zigzag/ );
    assert.equal( dom( '#ivt option:not(.hidden)' ).length, 0, 'no item view type left to choose' );
} );

test( 'a view type the settings limit to one item view type hides the select and saves that one', async () => {
    // a gallery as form_block_fields.tpl draws it with ViewTypeItemViewTypes[carousel]=carousel (itemViewTypeChoices()):
    // carousel offers only carousel, no "Default of the view type"; sushi_bar offers two
    document.body.innerHTML =
        '<div class="layouts-form"><form method="post" class="block-form" name="design_edit">' +
        '<div class="master-slave-selects">' +
        '<div class="row-input"><select id="vt" name="view_type" class="master view-type js-skip-on-change">' +
        '<option value="sushi_bar" selected>sushi_bar</option><option value="carousel">carousel</option></select></div>' +
        '<div class="row-input" id="ivt-row"><select id="ivt" name="item_view_type" class="slave">' +
        '<option value="carousel" data-master="carousel">carousel</option>' +
        '<option value="overlay" data-master="sushi_bar" selected>overlay</option>' +
        '<option value="line" data-master="sushi_bar">line</option></select></div>' +
        '</div></form></div>';
    saves = [];
    const form = dom( 'form' );
    const view = Object.assign( Object.create( Form ), {
        $: ( selector ) => form.find( selector ), is_query_form: false, form_id: 'design_edit',
        model: { save_via_form: () => saves.push( dom( 'form' ).serialize() ) }
    } );
    dom( '.layouts-form' ).on( 'submit', 'form', ( e ) => view.$submit( e ) ).on( 'change', ( e ) => view.$submit( e ) );
    core.ui.master_slave_selects( document.querySelectorAll( '.master-slave-selects .master' ) );
    assert.equal( dom( '#ivt option:not(.hidden)' ).length, 2, 'sushi_bar: its two item view types' );

    choose( 'vt', 'carousel' );
    await wait( 450 );
    assert.deepEqual( Array.from( document.querySelectorAll( '#ivt option:not(.hidden)' ), ( o ) => o.value ), [ 'carousel' ] );
    assert.equal( document.getElementById( 'ivt' ).value, 'carousel', 'the one left is chosen' );
    assert.equal( getComputedStyle( document.getElementById( 'ivt-row' ) ).display, 'none', 'and its select hidden' );
    assert.ok( saves.length >= 1 );
    assert.match( saves[ saves.length - 1 ], /view_type=carousel&item_view_type=carousel|item_view_type=carousel.*view_type=carousel/ );
} );
