/**
 * Mobile behaviour for the layouts editor.
 *
 * explayouts-mobile.css does the arranging. Three things cannot be done in a
 * stylesheet, and they are all that is here:
 *
 *   1. The header's height. Everything below it is `position: fixed` with a
 *      hard coded `top`, so once the header is allowed to wrap, its real
 *      height has to be published as --exp-header-h for the rest to sit under
 *      it rather than behind it.
 *
 *   2. The properties drawer. A panel that opens has to be opened by
 *      something: a button, selecting a block, and closing again on the scrim,
 *      the close button or Escape.
 *
 *   3. Dragging by finger. The editor sorts blocks with jQuery UI sortable,
 *      which listens for mouse events and nothing else, so on a touch screen
 *      blocks could not be moved at all. The listeners below turn a drag of
 *      the finger into the mouse events sortable is waiting for, while leaving
 *      taps and scrolling alone.
 *
 * Nothing here runs above the breakpoint, and everything it adds is removed
 * again when the viewport grows past it, so rotating a phone into landscape or
 * docking a tablet gives back the desktop editor rather than a half mobile one.
 */
( function () {
    'use strict';

    var BREAKPOINT = '(max-width: 820px)';
    var DRAG_SLOP  = 8;          // px of finger movement before it is a drag
    var HANDLES    = '.handle, .block-header, .ui-sortable-handle';

    var app     = null;
    var sidebar = null;
    var header  = null;
    var scrim   = null;
    var toggle  = null;
    var closer  = null;
    var mql     = window.matchMedia( BREAKPOINT );

    function isMobile()
    {
        return mql.matches;
    }

    // ── 1. header height ────────────────────────────────────────────────────

    function publishHeaderHeight()
    {
        if ( !header )
            return;

        var h = Math.ceil( header.getBoundingClientRect().height );

        // Below 50px means the header has not been drawn yet; the stylesheet
        // default is the right answer until it has.
        if ( h > 0 )
            document.documentElement.style.setProperty( '--exp-header-h', h + 'px' );
    }

    function watchHeader()
    {
        if ( !header )
            return;

        publishHeaderHeight();

        if ( window.ResizeObserver )
        {
            new ResizeObserver( publishHeaderHeight ).observe( header );
        }
        else
        {
            window.addEventListener( 'resize', publishHeaderHeight );
            setInterval( publishHeaderHeight, 1000 );
        }
    }

    // ── 2. the drawer ───────────────────────────────────────────────────────

    function hasSelection()
    {
        // The panel renders .not-selected - "Select a block to access its
        // properties." - whenever nothing is selected. Its absence is the only
        // signal the app gives that something now is.
        return !!sidebar && !sidebar.querySelector( '.not-selected' );
    }

    function openDrawer()
    {
        if ( isMobile() )
            app.classList.add( 'exp-sidebar-open' );
    }

    function closeDrawer()
    {
        app.classList.remove( 'exp-sidebar-open' );
    }

    function refreshToggle()
    {
        if ( !toggle )
            return;

        var selected = hasSelection();
        toggle.classList.toggle( 'has-selection', selected );
        toggle.textContent = 'Properties';
        toggle.setAttribute( 'aria-label',
            selected ? 'Show the properties of the selected block'
                     : 'Show the properties panel' );
    }

    function buildDrawerControls()
    {
        if ( scrim )
            return;

        scrim = document.createElement( 'button' );
        scrim.type = 'button';
        scrim.className = 'exp-scrim';
        scrim.setAttribute( 'aria-label', 'Close the properties panel' );
        scrim.addEventListener( 'click', closeDrawer );
        app.appendChild( scrim );

        toggle = document.createElement( 'button' );
        toggle.type = 'button';
        toggle.className = 'exp-drawer-toggle';
        toggle.addEventListener( 'click', openDrawer );
        app.appendChild( toggle );

        closer = document.createElement( 'button' );
        closer.type = 'button';
        closer.className = 'exp-drawer-close';
        closer.innerHTML = '<i class="material-icons">close</i>';
        closer.setAttribute( 'aria-label', 'Close the properties panel' );
        closer.addEventListener( 'click', closeDrawer );
        sidebar.appendChild( closer );

        refreshToggle();
    }

    /**
     * Which block the panel is currently showing, or null for none.
     *
     * The canvas marks the selected block with `editing` and the panel's forms
     * post to .../blocks/<id>. The panel is the better source: it says what is
     * actually loaded rather than what has been clicked, so a drawer opened off
     * it is never opened onto the previous block's properties.
     */
    function shownBlockId()
    {
        if ( !sidebar )
            return null;

        var form = sidebar.querySelector( 'form[action*="/blocks/"], [data-form*="/blocks/"]' );
        if ( !form )
            return null;

        var url = form.getAttribute( 'action' ) || form.getAttribute( 'data-form' ) || '';
        var found = url.match( /\/blocks\/(\d+)/ );
        return found ? found[1] : null;
    }

    function watchSelection()
    {
        var was  = hasSelection();
        var last = shownBlockId();

        new MutationObserver( function () {
            var now = hasSelection();
            var id  = shownBlockId();

            // Open when something is newly being shown, and open again when it
            // is a different block than last time. Only checking the first of
            // those was the whole bug: after the first block the panel never
            // went back to showing nothing, so choosing a second block changed
            // what was in the drawer without ever opening it again, and there
            // was no way to reach a block's properties by tapping it.
            //
            // A redraw of the same block still does not reopen: closing the
            // drawer to look at the canvas behind it has to stick.
            if ( now && ( !was || ( id && id !== last ) ) )
                openDrawer();
            else if ( !now && was )
                closeDrawer();

            was  = now;
            last = id;
            refreshToggle();
        } ).observe( sidebar, { childList: true, subtree: true, attributes: true,
                                attributeFilter: [ 'action', 'data-form' ] } );
    }

    /**
     * Tapping a block on the canvas brings up its properties.
     *
     * The observer above covers it once the panel has re-rendered, but that is
     * a request away and the drawer should answer the tap, not the response to
     * it. Controls inside a block are excluded: the overflow menu, the drag
     * handle and any link or button are doing their own job, and one of them
     * opening the drawer over the menu it just opened would be worse than
     * nothing.
     */
    function watchCanvasTaps()
    {
        document.addEventListener( 'click', function ( e ) {
            if ( !isMobile() || !e.target.closest )
                return;

            if ( !e.target.closest( '.main-content [data-block]' ) )
                return;

            if ( e.target.closest( 'a, button, input, select, textarea, .dropdown, .handle, .ui-sortable-handle' ) )
                return;

            openDrawer();
        }, true );
    }

    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' && app && app.classList.contains( 'exp-sidebar-open' ) )
            closeDrawer();
    } );

    // ── 3. dragging by finger ───────────────────────────────────────────────
    //
    // jQuery UI sortable is bound to mousedown/mousemove/mouseup. A touch
    // screen sends none of those until a tap is over, by which time a drag is
    // not a drag any more. So a finger held on a drag handle and moved is
    // replayed as the mouse gestures sortable expects.
    //
    // The mouse down is deliberately not sent on touchstart but on the first
    // movement past DRAG_SLOP. Sending it immediately would mean every tap on a
    // block header began a drag that had to be cancelled, and every attempt to
    // scroll the canvas with a finger that happened to start on a block would
    // move the block instead of the page.

    var drag = null;

    function mouseFromTouch( type, touch )
    {
        var e = document.createEvent( 'MouseEvents' );
        e.initMouseEvent( type, true, true, window, 1,
                          touch.screenX, touch.screenY,
                          touch.clientX, touch.clientY,
                          false, false, false, false, 0, null );
        return e;
    }

    function onTouchStart( e )
    {
        if ( !isMobile() || e.touches.length !== 1 )
            return;

        var target = e.target.closest ? e.target.closest( HANDLES ) : null;
        if ( !target )
            return;

        // A control inside the handle is a button, not a grip.
        if ( e.target.closest( 'a, button, input, select, textarea, .dropdown' ) )
            return;

        var t = e.touches[0];
        drag = { target: e.target, x: t.clientX, y: t.clientY, started: false };
    }

    function onTouchMove( e )
    {
        if ( !drag || e.touches.length !== 1 )
            return;

        var t = e.touches[0];

        if ( !drag.started )
        {
            if ( Math.abs( t.clientX - drag.x ) < DRAG_SLOP &&
                 Math.abs( t.clientY - drag.y ) < DRAG_SLOP )
                return;                                  // still just a touch

            drag.started = true;
            app.classList.add( 'exp-dragging' );
            drag.target.dispatchEvent( mouseFromTouch( 'mousedown', t ) );
        }

        // Past this point the finger belongs to the block, not to the page.
        e.preventDefault();
        drag.target.dispatchEvent( mouseFromTouch( 'mousemove', t ) );
    }

    function endTouch( e )
    {
        if ( !drag )
            return;

        if ( drag.started )
        {
            var t = ( e.changedTouches && e.changedTouches[0] ) || { screenX: 0, screenY: 0, clientX: drag.x, clientY: drag.y };
            drag.target.dispatchEvent( mouseFromTouch( 'mouseup', t ) );
            app.classList.remove( 'exp-dragging' );
        }
        // If it never started it was a tap, and the browser's own click is on
        // its way. Nothing to undo.

        drag = null;
    }

    function bindTouch()
    {
        document.addEventListener( 'touchstart', onTouchStart, { passive: true } );
        document.addEventListener( 'touchmove', onTouchMove, { passive: false } );
        document.addEventListener( 'touchend', endTouch );
        document.addEventListener( 'touchcancel', endTouch );
    }

    // ── wiring ──────────────────────────────────────────────────────────────

    function applyMode()
    {
        if ( !app )
            return;

        if ( isMobile() )
        {
            app.classList.add( 'exp-mobile' );
        }
        else
        {
            app.classList.remove( 'exp-mobile' );
            app.classList.remove( 'exp-sidebar-open' );
        }

        publishHeaderHeight();
    }

    function start()
    {
        app = document.getElementById( 'app' );
        if ( !app )
            return;

        // The app draws itself into #app after its bundle has run, so wait for
        // the parts rather than for an event that is never fired.
        var ready = new MutationObserver( function () {
            header  = header  || app.querySelector( '.app-header' );
            sidebar = sidebar || app.querySelector( '#sidebar' );

            if ( !header || !sidebar )
                return;

            ready.disconnect();
            watchHeader();
            buildDrawerControls();
            watchSelection();
            watchCanvasTaps();
            applyMode();
        } );

        ready.observe( app, { childList: true, subtree: true } );

        if ( mql.addEventListener )
            mql.addEventListener( 'change', applyMode );
        else if ( mql.addListener )
            mql.addListener( applyMode );

        bindTouch();
    }

    if ( document.readyState === 'loading' )
        document.addEventListener( 'DOMContentLoaded', start );
    else
        start();
} )();
