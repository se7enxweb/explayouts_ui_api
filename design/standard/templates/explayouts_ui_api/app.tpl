<!DOCTYPE html>
<html lang="{$locale|wash()}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#383838">

    <meta name="nglayouts-route-prefix" content="{$route_prefix|wash()}">
    <meta name="nglayouts-base-path" content="{$base_path|wash()}">
    <meta name="ngcb-base-path" content="{$cb_base_path|wash()}">
    <meta name="ezxform-token" content="{$ezxform_token|wash()}">

    <title>{$page_title|wash()}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/extension/explayouts_ui_api/design/standard/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/extension/explayouts_ui_api/design/standard/images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/extension/explayouts_ui_api/design/standard/images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/extension/explayouts_ui_api/design/standard/images/favicon/manifest.json">
    <link rel="mask-icon" href="/extension/explayouts_ui_api/design/standard/images/favicon/safari-pinned-tab.svg" color="#a477fc">
    <link rel="shortcut icon" href="/extension/explayouts_ui_api/design/standard/images/favicon/favicon.ico">
    <meta name="msapplication-config" content="/extension/explayouts_ui_api/design/standard/images/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    {foreach $custom_stylesheets as $stylesheet}
        <link rel="stylesheet" href="{$stylesheet|wash()}" />
    {/foreach}

    <link rel="stylesheet" href="{'stylesheets/netgen-layouts.css'|ezdesign('no')}">
    {literal}<style>
        #ngc-size-warning { display: none !important; }
        .left-toolbar .button.js-open.active + .left-toolbar-panels .left-panel { display: block !important; }
        .add-block-btn .font-icon::before { content: "\e900"; }
        .is-dragging [data-block] { pointer-events: none; }
        .is-dragging [data-zone] { box-shadow: inset 0 0 0 2px #4a90e2; }
        .zone { min-height: 80px; position: relative; }
        .zone::before { content: attr(data-zone); display: block; color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 8px; pointer-events: none; }
        .zone-body { min-height: 60px; }
        #ng-cancel-link:not(.btn) { position: fixed; top: 10px; right: 10px; z-index: 10000; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.6rem 1rem; border-radius: 4px; background: #2563eb; color: #fff; font-family: Roboto, Helvetica Neue, sans-serif; font-size: 0.875rem; font-weight: 500; text-decoration: none; line-height: 1; cursor: pointer; border: 0; }
        #ng-cancel-link:not(.btn):hover { background: #1d4ed8; }
    </style>{/literal}
</head>
<body>
    <script>{literal}var nglayoutsServerReturnTo = '{/literal}{$return_to|wash('javascript')}{literal}';{/literal}</script>
    {literal}<script>
    (function(){
        var returnTo = nglayoutsServerReturnTo || sessionStorage.getItem('nglayouts_return_to');
        if (!returnTo && document.referrer) returnTo = document.referrer;
        if (returnTo) sessionStorage.setItem('nglayouts_return_to', returnTo);
        if ( ( window.location.hash === '' || window.location.hash === '#' ) && returnTo )
        {
            window.location.replace( returnTo );
            return;
        }
        if ( ( window.location.hash === '' || window.location.hash === '#' ) && !returnTo )
        {
            var m = document.referrer.match( /\/content\/view\/full\/(\d+)/ );
            if ( m )
            {
                window.location.replace( '/content/view/full/' + m[1] );
                return;
            }
        }
    })();
    </script>{/literal}
    <div id="app" class="ngc" data-version="{$app_version|wash()} {$edition|wash()}"></div>
    <a id="ng-cancel-link" href="#">Cancel</a>
    {literal}<script>
    (function(){
        var link = document.getElementById('ng-cancel-link');
        if (!link) return;
        var returnTo = nglayoutsServerReturnTo || sessionStorage.getItem('nglayouts_return_to');
        if (!returnTo && document.referrer) returnTo = document.referrer;
        if (returnTo) link.href = returnTo;
        link.addEventListener('click', function(e){
            if (!returnTo) {
                e.preventDefault();
                window.history.back();
            }
        });

        function placeCancel() {
            if (link._placed) return;
            var createBtn = document.querySelector('.btn-primary.action_apply, .btn-primary');
            if (!createBtn) return;
            var parent = createBtn.parentNode;
            if (!parent) return;
            link._placed = true;
            link.className = 'btn btn-primary action_apply green';
            link.style.cssText = 'margin-right: 8px;';
            parent.insertBefore(link, createBtn);
        }

        var app = document.getElementById('app');
        if (app) {
            new MutationObserver(function(){ placeCancel(); }).observe(app, { childList: true, subtree: true });
        }
        setTimeout(placeCancel, 1000);
    })();
    </script>{/literal}

    {if $google_maps_api_key|ne('')}
        <script async src="https://maps.googleapis.com/maps/api/js?key={$google_maps_api_key|urlencode()}&loading=async&callback=initMap"></script>
    {/if}

    {foreach $custom_javascripts as $javascript}
        <script src="{$javascript|wash()}" defer></script>
    {/foreach}

    <script src="{'vendor/ckeditor/ckeditor.js'|ezdesign('no')}" defer></script>
    <script src="{'vendor/ace-editor/ace.js'|ezdesign('no')}" defer></script>
    <script src="{'javascript/netgen-layouts.js'|ezdesign('no')}" defer></script>
    {literal}<script>
    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('.dropdown-toggle');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = toggle.closest('.dropdown');
            if (dropdown) {
                var wasOpen = dropdown.classList.contains('open');
                document.querySelectorAll('.app-header .dropdown.open').forEach(function(d) { d.classList.remove('open'); });
                if (!wasOpen) dropdown.classList.add('open');
            }
            return;
        }
        var openDropdown = document.querySelector('.app-header .dropdown.open');
        if (openDropdown && !openDropdown.contains(e.target)) {
            openDropdown.classList.remove('open');
        }
    }, true);
    </script>{/literal}
    {literal}<script>
    (function(){
        function definitionFromClass(el) {
            var m = el.className.match(/icn-([a-zA-Z0-9_-]+)/);
            return m ? m[1] : '';
        }
        function initButtons(root) {
            root.querySelectorAll('.add-block-btn').forEach(function(btn){
                if (btn.draggable) return;
                btn.draggable = true;
                btn.dataset.definition = definitionFromClass(btn);
            });
        }
        var app = document.getElementById('app');
        if (app) {
            new MutationObserver(function(mutations){
                mutations.forEach(function(mutation){
                    if (mutation.type !== 'childList') return;
                    mutation.addedNodes.forEach(function(node){
                        if (node.nodeType === 1) initButtons(node);
                    });
                });
            }).observe(app, { childList: true, subtree: true });
        }
        initButtons(document);

        document.addEventListener('dragstart', function(e){
            var btn = e.target.closest('.add-block-btn');
            if (!btn) return;
            document.body.classList.add('is-dragging');
            e.dataTransfer.setData('text/plain', btn.dataset.definition);
            e.dataTransfer.setData('definition', btn.dataset.definition);
        }, true);

        document.addEventListener('dragend', function(e){
            document.body.classList.remove('is-dragging');
        }, true);

        document.addEventListener('dragover', function(e){
            if (e.target.closest('[data-zone]')) e.preventDefault();
        }, true);

        document.addEventListener('drop', function(e){
            var zone = e.target.closest('[data-zone]');
            if (!zone) return;
            e.preventDefault();
            var definition = e.dataTransfer.getData('definition') || e.dataTransfer.getData('text/plain');
            if (!definition) return;
            var layoutId = (window.location.hash.match(/#layout\/(\d+)/) || [])[1];
            var zoneIdentifier = zone.getAttribute('data-zone');
            if (!layoutId || !zoneIdentifier) return;
            var body = new URLSearchParams();
            body.append('layout_id', layoutId);
            body.append('zone_identifier', zoneIdentifier);
            body.append('definition_identifier', definition);
            var tokenMeta = document.querySelector('meta[name="ezxform-token"]');
            if (tokenMeta) body.append('ezxform_token', tokenMeta.content);
            fetch('/explayouts_ui_api/app/api/eng/blocks', { method: 'POST', body: body, credentials: 'same-origin' }).then(function(r){
                if (r.ok) window.location.reload();
            });
        }, true);
    })();
    </script>{/literal}
    {literal}<script>
    (function(){
        if (!sessionStorage.getItem('nglayouts_return_to') && document.referrer) {
            sessionStorage.setItem('nglayouts_return_to', document.referrer);
        }

        function checkRedirect() {
            var returnTo = sessionStorage.getItem('nglayouts_return_to');
            if (!returnTo) return;
            sessionStorage.removeItem('nglayouts_return_to');
            setTimeout(function() { window.location.href = returnTo; }, 100);
        }

        function shouldRedirect(url, method) {
            if (typeof url !== 'string') return false;
            method = (method || 'GET').toUpperCase();
            return url.match(/\/api\/layouts\/\d+\/publish([?#]|$)/) ||
                   (url.match(/\/api\/layouts\/\d+\/draft([?#]|$)/) && method === 'DELETE');
        }

        if (window.fetch) {
            var origFetch = window.fetch;
            window.fetch = function(url, options) {
                var method = options && options.method ? options.method : 'GET';
                var urlString = typeof url === 'string' ? url : (url && url.url ? url.url : '');
                return origFetch.apply(this, arguments).then(function(response) {
                    if (response.ok && shouldRedirect(urlString, method)) {
                        checkRedirect();
                    }
                    return response;
                });
            };
        }

        var origXhrOpen = XMLHttpRequest.prototype.open;
        var origXhrSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
            this._ngMethod = method;
            this._ngUrl = url;
            return origXhrOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function(body) {
            var xhr = this;
            var method = xhr._ngMethod;
            var url = xhr._ngUrl;
            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300 && shouldRedirect(url, method)) {
                    checkRedirect();
                }
            });
            return origXhrSend.apply(this, arguments);
        };
    })();
    </script>{/literal}
</body>
</html>
