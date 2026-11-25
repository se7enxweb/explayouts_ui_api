/**
 * The block types are asked for with the layout the editor opens (config/block_types?layout=<id>) and asked for again
 * when another layout is opened: the server answers with the block definitions of the site that layout belongs to
 * (se7enxweb/exponential#364). Module ./application (src/app-patches/modules/045-application.js) run on its own with
 * stand-ins for the core, the page and the layout model.
 *
 * Run: npm ci && npm test (in extension/explayouts_ui_api)
 *
 * @license GPL-2.0-or-later
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { root } from './support/editor-page.mjs';

const source = readFileSync( resolve( root, 'src/app-patches/modules/045-application.js' ), 'utf8' );

/** A promise that also has .then as jQuery's $.when gives it: resolved with no value. */
const done = () => Promise.resolve();

function app() {
    const asked = [];
    const blockTypes = {
        loaded: null,
        fetch( options ) { asked.push( options && options.data ); return done(); },
        fetch_once() { return this.loaded || ( this.loaded = this.fetch.apply( this, arguments ) ); }
    };
    const fetchedOnce = () => ( { fetch_once: done } );
    const core = {
        $: { when: ( ...a ) => Promise.all( a ) },
        router: { params: {}, route_name: 'layout' },
        g: { config: fetchedOnce(), shared_layouts: fetchedOnce(), layout_types: fetchedOnce(), block_types: blockTypes, layout: null }
    };
    class Layout {
        constructor( attributes ) { this.id = attributes.id; }
        set() {}
        fetch() { return done(); }
        fetch_once() { return done(); }
    }
    const modules = { '../core': core, '../page': { extend: ( spec ) => spec }, '../models/layout': Layout };
    const module = { exports: null };
    ( 0, eval )( '(' + source + ')' )( ( name ) => modules[ name ], module, {} );
    const open = ( id ) => new Promise( ( resolveOpen, fail ) => {
        core.router.params = { id: String( id ), locale: 'eng' };
        module.exports.deps( ( error ) => ( error ? fail( error ) : resolveOpen() ) );
    } );
    return { asked, open };
}

test( 'the block types are asked for with the layout, once per layout, again for another one', async () => {
    const editor = app();
    await editor.open( 7 );
    assert.deepEqual( editor.asked, [ { layout: '7' } ], 'the first layout: asked with ?layout=7' );
    await editor.open( 7 );
    assert.deepEqual( editor.asked, [ { layout: '7' } ], 'the same layout again: not asked again' );
    await editor.open( 12 );
    assert.deepEqual( editor.asked, [ { layout: '7' }, { layout: '12' } ], 'another layout: asked again, with its id' );
} );
