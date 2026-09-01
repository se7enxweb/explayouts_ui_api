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
    <link rel="stylesheet" href="{'stylesheets/netgen-layouts-admin.css'|ezdesign('no')}">
    {literal}<style>
        #ngc-size-warning { display: none !important; }
        .left-toolbar .button.js-open.active + .left-toolbar-panels .left-panel { display: block !important; }
        .add-block-btn .font-icon::before { content: "\e900"; }
        .is-dragging [data-block] { pointer-events: none; }
        .is-dragging [data-zone] { box-shadow: inset 0 0 0 2px #4a90e2; }
        .zone { min-height: 80px; position: relative; }
        .zone::before { content: attr(data-zone); display: block; color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 8px; pointer-events: none; }
        .zone-body { min-height: 60px; }
        .block-content { background: #fff; color: #333; padding: 8px; }
        .block-items { margin: 0; padding-left: 1.2em; }
        .block-items li { background: #fff; color: #333; padding: 2px 0; }
        #ng-cancel-link:not(.btn) { position: fixed; top: 10px; right: 10px; z-index: 10000; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.6rem 1rem; border-radius: 4px; background: #2563eb; color: #fff; font-family: Roboto, Helvetica Neue, sans-serif; font-size: 0.875rem; font-weight: 500; text-decoration: none; line-height: 1; cursor: pointer; border: 0; }
        #ng-cancel-link:not(.btn):hover { background: #1d4ed8; }

        /* Right sidebar / query browser styles to match Nexus */
        #aside-tabs {
            background: #404040;
            color: #fff;
            padding: 15px;
            font-size: 16px;
            box-sizing: border-box;
        }
        #aside-tabs h4 {
            font-size: 11px;
            font-weight: 400;
            margin: 16px 0 12px;
            text-transform: uppercase;
            color: #9b9b9b;
        }
        #aside-tabs .aside-tab-control {
            list-style-type: none;
            display: flex;
            margin: 0 0 16px;
            padding: 0;
        }
        #aside-tabs .aside-tab-control li {
            flex: 1;
            display: flex;
        }
        #aside-tabs .aside-tab-control li a {
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 14px;
            border: 1px solid #fff;
            color: #fff;
            width: 100%;
            padding: 9px 4px;
            display: flex;
            cursor: pointer;
            text-decoration: none;
        }
        #aside-tabs .aside-tab-control li:first-child a {
            border-radius: 2px 0 0 2px;
        }
        #aside-tabs .aside-tab-control li:last-child a {
            border-radius: 0 2px 2px 0;
        }
        #aside-tabs .aside-tab-control li.active a {
            background: #fff;
            border: 0;
            color: #404040;
        }

        #aside-tabs .tab-pane {
            display: none;
            box-sizing: border-box;
        }
        #aside-tabs .tab-pane.active {
            display: block;
        }
        #aside-tabs .sidebar-panel + .sidebar-panel {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid hsla(0, 0%, 100%, .2);
        }

        #aside-tabs .layouts-form,
        #aside-tabs .query-form,
        #aside-tabs .content-form {
            color: #fff;
        }

        #aside-tabs .row-input,
        #aside-tabs .checkbox {
            margin-bottom: 16px;
        }
        #aside-tabs .row-input > label,
        #aside-tabs .xeditable .current > label {
            display: block;
            font-weight: 400;
            font-size: 12px;
            color: #9b9b9b;
            margin: 0 0 4px;
        }

        #aside-tabs .row-input input[type="text"],
        #aside-tabs .row-input input[type="number"],
        #aside-tabs .row-input input[type="url"],
        #aside-tabs .row-input input[type="email"],
        #aside-tabs .row-input textarea,
        #aside-tabs select,
        #aside-tabs .xeditable .js-edit {
            border: 0;
            box-shadow: none;
            border-radius: 2px;
            padding: 0 12px;
            width: 100%;
            height: 36px;
            font-size: 12px;
            margin: 0 0 16px;
            background: #666;
            color: #fff;
            box-sizing: border-box;
            appearance: none;
            -webkit-appearance: none;
        }
        #aside-tabs select {
            background: #666 url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIiIGhlaWdodD0iOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNDEiPjxwYXRoIGQ9Ik01LjUzIDcuNDlMLjIgMi4xNWEuNjYuNjYgMCAwIDEgMC0uOTNMLjgyLjU5YS42Ni42NiAwIDAgMS45MyAwTDYgNC44MiAxMC4yNS42YS42Ni42NiAwIDAxLjkzIDBsLjYzLjYzYy4yNS4yNS4yNS42NyAwIC45M0w2LjQ3IDcuNDlhLjY2LjY2IDAgMDEtLjk0IDB6IiBmaWxsPSIjZmZmIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48L3N2Zz4=') no-repeat;
            background-position: right 8px center;
            background-size: 13px auto;
            padding-right: 36px;
            cursor: pointer;
        }
        #aside-tabs select[multiple] {
            height: auto;
            min-height: 140px;
            padding: 4px 0;
            background-image: none;
        }
        #aside-tabs select option,
        #aside-tabs select optgroup,
        #aside-tabs select[multiple] option,
        #aside-tabs select[multiple] optgroup {
            color: #fff;
            background: #666;
        }
        #aside-tabs select[multiple] option {
            padding: 2px 12px;
        }
        #aside-tabs select[multiple] optgroup[label] {
            padding: 2px 12px;
        }
        #aside-tabs select[multiple] optgroup[label] option {
            margin: 0 -12px;
        }
        #aside-tabs select option:checked,
        #aside-tabs select option:hover,
        #aside-tabs select[multiple] option:checked,
        #aside-tabs select[multiple] option:hover {
            background: #4a90e2;
        }
        #aside-tabs select option.hidden,
        #aside-tabs select[multiple] option.hidden {
            display: none;
        }
        #aside-tabs .row-input textarea {
            height: auto;
            min-height: 120px;
            padding-top: 6px;
            resize: vertical;
        }
        #aside-tabs .input-note {
            font-size: 10px;
            font-style: italic;
            margin: -12px 0 19px;
            color: #9b9b9b;
        }

        /* Checkboxes */
        #aside-tabs input[type="checkbox"] {
            position: absolute;
            left: -9999em;
        }
        #aside-tabs input[type="checkbox"] + label {
            display: block;
            position: relative;
            padding: 6px 6px 6px 32px;
            margin: 0 0 8px;
            cursor: pointer;
            font-size: 12px;
            color: #9b9b9b;
            line-height: 1.4;
        }
        #aside-tabs input[type="checkbox"] + label::before {
            content: 'check_box_outline_blank';
            font-family: 'Material Icons';
            font-size: 24px;
            position: absolute;
            color: #fff;
            left: 0;
            top: .2em;
            font-weight: normal;
            font-style: normal;
            text-transform: none;
            letter-spacing: normal;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            -moz-osx-font-smoothing: grayscale;
            font-feature-settings: 'liga';
        }
        #aside-tabs input[type="checkbox"]:checked + label::before {
            content: 'check_box';
        }

        #aside-tabs [data-compound-checkbox] .children { display: none; padding-left: 1.625em; margin-bottom: 16px; }
        #aside-tabs [data-compound-checkbox] .children > .row-input,
        #aside-tabs [data-compound-checkbox] .children > .checkbox {
            margin-bottom: 16px;
        }
        #aside-tabs [data-compound-checkbox] input[type="checkbox"]:checked ~ .children { display: block; }
        #aside-tabs [data-compound-reverse] .children { display: block; padding-left: 0; }
        #aside-tabs [data-compound-reverse] input[type="checkbox"]:checked ~ .children { display: none; }

        /* Toggle links / headings */
        #aside-tabs .toggle-link {
            font-size: 12px;
            display: block;
            margin: 0 0 16px;
            text-transform: uppercase;
            color: #fff;
            text-decoration: none;
        }
        #aside-tabs .toggle-link::after {
            content: 'arrow_drop_down';
            font-family: 'Material Icons';
            display: inline-block;
            vertical-align: middle;
            float: right;
            transform: rotate(-90deg);
            font-size: 20px;
        }
        #aside-tabs .toggle-link[aria-expanded="true"]::after {
            transform: none;
        }
        #aside-tabs .sub-toggle {
            display: inline-block;
            text-transform: none;
        }
        #aside-tabs .sub-toggle::after {
            float: none;
        }

        /* Browse input */
        #aside-tabs .input-browse {
            display: flex;
            align-items: center;
            background: #666;
            border-radius: 2px;
            height: 36px;
            margin: 0 0 16px;
            box-sizing: border-box;
            overflow: hidden;
        }
        #aside-tabs .input-browse .js-clear {
            order: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 36px;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            background: #666;
            border-right: 1px solid #404040;
            border-radius: 2px 0 0 2px;
        }
        #aside-tabs .input-browse .js-clear:hover {
            background: #595959;
        }
        #aside-tabs .input-browse .js-trigger {
            order: 1;
            flex: 1;
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            height: 36px;
            font-size: 12px;
            background: #666;
            border-radius: 0 2px 2px 0;
        }
        #aside-tabs .input-browse .js-trigger:hover {
            background: #595959;
        }
        #aside-tabs .input-browse .js-name {
            flex: 1;
            padding: 0 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #aside-tabs .input-browse .material-icons {
            font-size: 20px;
            color: #fff;
            pointer-events: none;
            padding: 0 8px;
        }
        #aside-tabs .input-browse.item-empty .js-trigger {
            color: #bbb;
        }
        #aside-tabs .input-browse.item-empty .js-clear {
            display: none;
        }

        /* View in CMS */
        .js-view-cms {
            display: block;
            margin: -12px 0 16px;
            padding: 8px 12px;
            border: 1px dashed #9b9b9b;
            border-radius: 2px;
            text-align: center;
            font-size: 11px;
            line-height: 1.4;
            text-transform: uppercase;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
        }
        .js-view-cms:hover {
            background: rgba(255, 255, 255, 0.05);
            text-decoration: none;
        }

        /* Xeditable current value display */
        #aside-tabs .xeditable .js-edit {
            margin: 0 0 16px;
            background: #666;
            height: 36px;
            font-size: 12px;
            color: #fff;
            display: flex;
            align-items: center;
            text-decoration: none;
            border-radius: 2px;
            padding: 0;
        }
        #aside-tabs .xeditable .js-edit:hover {
            background: #595959;
        }
        #aside-tabs .xeditable .js-edit .text {
            flex: 1;
            padding: 0 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #aside-tabs .xeditable .js-edit .icon {
            background: #595959;
            padding: 0 24px;
            border-radius: 0 2px 2px 0;
            color: #fff;
            display: flex;
            align-items: center;
            height: 36px;
        }
        #aside-tabs .xeditable .actions {
            text-align: right;
            margin: 0 0 8px;
        }

        /* Add items button */
        #aside-tabs .add-items {
            margin: 0 0 24px;
            border: 1px dashed #9b9b9b;
            text-align: center;
            font-size: 10px;
            line-height: 36px;
            text-transform: uppercase;
            cursor: pointer;
            color: #fff;
            display: block;
        }
        #aside-tabs .add-items:hover {
            background: rgba(255, 255, 255, 0.05);
            text-decoration: none;
        }

        /* Value type select wrapper inside collection items */
        #aside-tabs .value-type-wrapper select {
            margin-bottom: 8px;
        }
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
    <script src="{'javascript/netgen-layouts.js'|ezdesign('no')}?v=20260833" defer></script>
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
    document.addEventListener('click', function(e) {
        var tabLink = e.target.closest('#aside-tabs .aside-tab-control a[role="tab"]');
        if (!tabLink) return;
        e.preventDefault();

        var controls = tabLink.getAttribute('aria-controls');
        var tabList = tabLink.closest('.aside-tab-control');
        if (!tabList || !controls) return;

        tabList.querySelectorAll('li').forEach(function(li) { li.classList.remove('active'); });
        tabLink.parentNode.classList.add('active');

        document.querySelectorAll('#aside-tabs .tab-pane').forEach(function(pane) { pane.classList.remove('active'); });
        var targetPane = document.getElementById(controls);
        if (targetPane) targetPane.classList.add('active');
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
    {literal}<script>
    (function(){
        var initInterval = setInterval(function(){
            var Core = window.Core;
            if (!Core || !Core.$) return;
            clearInterval(initInterval);
            init(Core);
        }, 500);

        function init(Core){
            var $ = Core.$;

        var modalHtml = '<div class="modal fade" id="exp-content-browser-modal" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document" style="width:700px;max-width:90vw;">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h4 class="modal-title">Add content to collection</h4></div>' +
            '<div class="modal-body">' +
            '<div class="exp-cb-search form-group"><input type="text" class="form-control" placeholder="Search..." /></div>' +
            '<div class="exp-cb-breadcrumbs" style="margin:0 0 10px;"></div>' +
            '<div class="exp-cb-list" style="max-height:400px;overflow:auto;"></div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button class="btn btn-default exp-cb-cancel" data-dismiss="modal">Cancel</button>' +
            '<button class="btn btn-primary exp-cb-apply">Add selected</button>' +
            '</div>' +
            '</div></div></div>';

        var $modal;
        var state = { parentId: 2, search: '', path: [{ node_id: 2, name: 'Content' }], singleSelect: false, callback: null };
        var pendingView = null;

        function ensureModal() {
            if ($modal) return;
            var $app = $('#app');
            if ($app.length === 0) $app = $(document.body);
            $app.append(modalHtml);
            $modal = $('#exp-content-browser-modal');
            $modal.on('click', '.exp-cb-cancel', function(e){ e.preventDefault(); $modal.modal('hide'); });
            $modal.on('click', '.exp-cb-apply', function(e){ onApply(); });
            $modal.on('keydown', '.exp-cb-search input', function(e){ if (e.keyCode === 13) { e.preventDefault(); state.search = $(this).val(); load(); } });
            $modal.on('change', '.exp-cb-search input', function(){ state.search = $(this).val(); });
            $modal.on('click', '.exp-cb-breadcrumbs a', function(e){ e.preventDefault(); var idx = parseInt($(this).data('idx')); state.path = state.path.slice(0, idx + 1); state.parentId = state.path[idx].node_id; state.search = ''; $modal.find('.exp-cb-search input').val(''); load(); });
            $modal.on('click', '.exp-cb-list .js-cb-browse', function(e){ e.preventDefault(); var nodeId = parseInt($(this).data('node-id')); var name = $(this).data('name'); state.parentId = nodeId; state.path.push({ node_id: nodeId, name: name }); state.search = ''; $modal.find('.exp-cb-search input').val(''); load(); });
            $modal.on('change', '.exp-cb-list .js-cb-select', function(){});
        }

        function renderBreadcrumbs() {
            var html = '';
            state.path.forEach(function(item, idx){
                html += (idx > 0 ? ' / ' : '') + '<a href="#" data-idx="' + idx + '">' + $('<div>').text(item.name).html() + '</a>';
            });
            $modal.find('.exp-cb-breadcrumbs').html(html);
        }

        function load() {
            var url = '/explayouts_ui_api/app/api/content_browser?parent_node_id=' + state.parentId + '&search=' + encodeURIComponent(state.search) + '&offset=0&limit=50';
            $modal.find('.exp-cb-list').html('<p>Loading...</p>');
            $.getJSON(url).done(function(data){
                var html = '';
                if (!data.values || data.values.length === 0) {
                    html = '<p>No content found.</p>';
                } else {
                    html += '<table class="table table-condensed"><tbody>';
                    data.values.forEach(function(item){
                        var cbId = 'exp-cb-' + item.node_id;
                        var inputType = state.singleSelect ? 'radio' : 'checkbox';
                        var inputName = state.singleSelect ? ' name="exp-cb-single"' : '';
                        html += '<tr>';
                        html += '<td><input type="' + inputType + '"' + inputName + ' id="' + cbId + '" class="js-cb-select" data-node-id="' + item.node_id + '" data-object-id="' + item.object_id + '" data-name="' + $('<div>').text(item.name).html() + '" />';
                        html += '<label for="' + cbId + '">' + $('<div>').text(item.name).html() + ' <small>(' + item.class_name + ')</small></label></td>';
                        html += '<td style="width:100px;">';
                        if (item.is_container) {
                            html += '<a href="#" class="js-cb-browse" data-node-id="' + item.node_id + '" data-name="' + $('<div>').text(item.name).html() + '">Open</a>';
                        }
                        html += '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                $modal.find('.exp-cb-list').html(html);
                renderBreadcrumbs();
            }).fail(function(){
                $modal.find('.exp-cb-list').html('<p>Failed to load content.</p>');
            });
        }

        function openModal(view) {
            pendingView = view;
            state.singleSelect = false;
            state.callback = null;
            ensureModal();
            $modal.find('.modal-title').text('Add content to collection');
            $modal.find('.exp-cb-apply').text('Add selected');
            state.parentId = 2;
            state.search = '';
            state.path = [{ node_id: 2, name: 'Content' }];
            $modal.find('.exp-cb-search input').val('');
            load();
            $modal.modal('show');
        }

        function openSingleSelectModal(callback, options) {
            options = options || {};
            pendingView = null;
            state.singleSelect = true;
            state.callback = callback;
            ensureModal();
            $modal.find('.modal-title').text(options.title || 'Select content');
            $modal.find('.exp-cb-apply').text(options.buttonText || 'Select');
            state.parentId = 2;
            state.search = '';
            state.path = [{ node_id: 2, name: 'Content' }];
            $modal.find('.exp-cb-search input').val('');
            load();
            $modal.modal('show');
        }

        function onApply() {
            var selected = [];
            $modal.find('.exp-cb-list .js-cb-select:checked').each(function(){
                selected.push($(this));
            });
            if (selected.length === 0) { $modal.modal('hide'); return; }

            if (state.singleSelect && typeof state.callback === 'function') {
                var $sel = selected[0];
                state.callback({
                    nodeId: parseInt($sel.data('node-id')),
                    objectId: parseInt($sel.data('object-id')),
                    name: String($sel.data('name') || '')
                });
                $modal.modal('hide');
                return;
            }

            if (!pendingView) return;
            selected = selected.map(function($el){ return parseInt($el.data('node-id')); });

            var model = pendingView.model;
            var blockId = model.get('block_id');
            var identifier = model.get('identifier') || 'default';
            var locale = model.get('locale') || 'eng';
            var valueType = model.get('canAddItems') ? (pendingView.$el.closest('.collection-items').find('.js-browser-item-type').val() || 'ez_location') : 'ez_location';
            var position = model.get('offset') || 0;

            var items = selected.reverse().map(function(nodeId){
                return { value: nodeId, value_type: valueType, item_type: 'manual', position: position };
            });

            $.ajax({
                url: '/explayouts_ui_api/app/api/' + locale + '/blocks/' + blockId + '/collections/' + identifier + '/items',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ items: items }),
                headers: { 'X-CSRF-Token': Core.g && Core.g.config ? Core.g.config.get('csrf_token') : '' }
            }).done(function(){
                $modal.modal('hide');
                if (model.fetch_results) model.fetch_results();
            }).fail(function(){
                $modal.modal('hide');
                alert('Failed to add items.');
            });
        }

        document.addEventListener('click', function(e){
            var target = e.target.closest ? e.target.closest('.add-items') : null;
            if (!target) return;
            var body = document.querySelector('.collection-items .body');
            if (!body) return;
            var view = $(body).data('_view');
            if (!view || !view.model) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            openModal(view);
        }, true);

        function triggerBrowserChange($wrap) {
            $wrap.find('input.js-value').trigger('change');
            $wrap[0].dispatchEvent(new CustomEvent('browser:change', { bubbles: true, detail: { instance: $wrap[0] } }));
        }

        document.addEventListener('click', function(e){
            if (!e.target.closest) return;
            var trigger = e.target.closest('.exp-input-browse .js-trigger');
            var clear = e.target.closest('.exp-input-browse .js-clear');
            if (!trigger && !clear) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            var $wrap = $(trigger || clear).closest('.exp-input-browse');
            var $input = $wrap.find('input.js-value');
            var $name = $wrap.find('.js-name');
            var $row = $wrap.closest('.row-input');
            var $cms = $row.find('.js-view-cms');
            var itemType = $wrap.find('input.js-item-type').val() || 'ibexa_content';

            if (clear) {
                $input.val('').trigger('change');
                $name.text($name.data('empty-note') || 'Select item');
                $wrap.addClass('item-empty');
                $cms.hide();
                triggerBrowserChange($wrap);
                return;
            }

            var title = 'Select content';
            var $label = $row.find('label').first();
            if ($label.length) title = 'Select ' + $label.text().trim();

            openSingleSelectModal(function(data){
                var value = (itemType === 'ibexa_location') ? data.nodeId : data.objectId;
                $input.val(value).trigger('change');
                $name.text(data.name);
                $wrap.removeClass('item-empty');
                $cms.attr('href', '/content/view/full/' + data.nodeId).show();
                triggerBrowserChange($wrap);
            }, { title: title, buttonText: 'Select' });
        }, true);

}
    })();
    </script>{/literal}
</body>
</html>
