<!DOCTYPE html>
<html lang="{$locale|wash()}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#383838">

    <meta name="explayouts-route-prefix" content="{$route_prefix|wash()}">
    <meta name="explayouts-base-path" content="{$base_path|wash()}">
    <meta name="ngcb-base-path" content="{$cb_base_path|wash()}">
    <meta name="ezxform-token" content="{$ezxform_token|wash()}">

    <title>{$page_title|wash()}</title>

    {* Through ezroot: an installation served from a sub path (/exponential6/) has the files below it. *}
    <link rel="apple-touch-icon" sizes="180x180" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/apple-touch-icon.png'|ezroot('no')}">
    <link rel="icon" type="image/png" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/favicon-32x32.png'|ezroot('no')}" sizes="32x32">
    <link rel="icon" type="image/png" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/favicon-16x16.png'|ezroot('no')}" sizes="16x16">
    <link rel="manifest" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/manifest.json'|ezroot('no')}">
    <link rel="mask-icon" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/safari-pinned-tab.svg'|ezroot('no')}" color="#a477fc">
    <link rel="shortcut icon" href="{'/extension/explayouts_ui_api/design/standard/images/favicon/favicon.ico'|ezroot('no')}">
    <meta name="msapplication-config" content="{'/extension/explayouts_ui_api/design/standard/images/favicon/browserconfig.xml'|ezroot('no')}">
    <meta name="theme-color" content="#ffffff">

    {foreach $custom_stylesheets as $stylesheet}
        <link rel="stylesheet" href="{$stylesheet|wash()}" />
    {/foreach}

    <link rel="stylesheet" href="{'stylesheets/netgen-layouts.css'|ezdesign('no')}">
    <link rel="stylesheet" href="{'stylesheets/netgen-layouts-admin.css'|ezdesign('no')}">
    {* Last of the three so it wins. The editor has no mobile layout of its own
       - upstream refuses below 800px instead - so this file is that layout. *}
    <link rel="stylesheet" href="{'stylesheets/explayouts-mobile.css'|ezdesign('no')}">
    <link rel="stylesheet" href="{'stylesheets/explayouts-tiptap.css'|ezdesign('no')}">
    {* The editor's own rules, last so they win (until 1.3.21 an inline style block here). *}
    <link rel="stylesheet" href="{'stylesheets/explayouts-editor.css'|ezdesign('no')}">
</head>
<body>
    {* The page's values and texts, as JSON data (no inline code; until 1.3.22 inline scripts): read by
       javascript/explayouts-app-head.js, which sets the globals the bundle and javascript/explayouts-app.js use. *}
    {def $explayouts_app_data = hash(
        'return_to', $return_to,
        'site_root', '/'|ezurl('no'),
        'i18n', hash(
            'content', 'Content'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'add_to_collection', 'Add content to collection'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'add_selected', 'Add selected'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'select_content', 'Select content'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'select', 'Select'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'select_label', 'Select %label'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'select_item', 'Select item'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'add_failed', 'Failed to add items.'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'search', 'Search...'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'cancel', 'Cancel'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'loading', 'Loading...'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'no_content', 'No content found.'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'open', 'Open'|i18n( 'design/standard/explayouts_ui_api/app' ),
            'load_failed', 'Failed to load content.'|i18n( 'design/standard/explayouts_ui_api/app' )
        )
    )}
    <script type="application/json" id="explayouts-app-data">{json_encode( $explayouts_app_data )|explode( '<' )|implode( '\u003c' )|explode( '>' )|implode( '\u003e' )}</script>
    {undef $explayouts_app_data}
    {include uri='design:explayouts_ui_api/spa_strings.tpl'}
    {* Blocking, here at the start of the body: the return target and the redirect of an empty hash come first. *}
    <script src="{'javascript/explayouts-app-head.js'|ezdesign('no')}"></script>
    {* Joined into the app-logo tooltip by the SPA. Skip the separator when
       there is no version, so the tooltip has no leading space. *}
    <div id="app" class="ngc" data-version="{if $app_version|ne('')}{$app_version|wash()} {/if}{$edition|wash()}"></div>
    <a id="ng-cancel-link" href="#">{'Cancel'|i18n( 'design/standard/explayouts_ui_api/app' )}</a>

    {if $google_maps_api_key|ne('')}
        <script async src="https://maps.googleapis.com/maps/api/js?key={$google_maps_api_key|urlencode()}&loading=async&callback=initMap"></script>
    {/if}

    {foreach $custom_javascripts as $javascript}
        <script src="{$javascript|wash()}" defer></script>
    {/foreach}

    {* No jQuery and no jQuery UI: the app bundle brings its own DOM layer and drag and drop. *}
    {* The rich text and code editors (Tiptap, built from src/editors/): window.CKEDITOR and window.ace with
       the calls the app bundle makes of them. Before the bundle: it registers CKEDITOR listeners on load. *}
    <script src="{'javascript/explayouts-tiptap.js'|ezdesign('no')}" defer></script>
    <script src="{'javascript/netgen-layouts.js'|ezdesign('no')}" defer></script>
    {* After the app bundle: it waits for the app to draw itself before it
       touches anything. *}
    <script src="{'javascript/explayouts-mobile.js'|ezdesign('no')}" defer></script>
    {* Blocking, after the tags of the deferred bundles, so it runs before them: the Cancel link, the form token on
       writes and the content browser dialog. *}
    <script src="{'javascript/explayouts-app.js'|ezdesign('no')}"></script>
</body>
</html>
