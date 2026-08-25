<?php
eZDebug::updateSettings( array( 'debug-enabled' => false ) );

$requestUri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
$path = parse_url( $requestUri, PHP_URL_PATH );
$path = preg_replace( '#^/index\.php/#', '/', $path );
$path = rtrim( $path, '/' );

$apiPath = '';
if ( preg_match( '#^/explayouts_ui_api/app(?:/(.*))?$#', $path, $m ) )
{
    $apiPath = isset( $m[1] ) ? $m[1] : '';
}

if ( strpos( $apiPath, 'api/' ) === 0 )
{
    $parts = explode( '/', substr( $apiPath, 4 ) );
    return expLayoutsUIApplicationApi::handle( $parts );
}

$tpl = eZTemplate::factory();

if ( $apiPath === 'layouts/form/create' )
{
    $tpl->setVariable( 'layout_types', expLayoutsLayoutType::getAvailableTypes() );
    $tpl->setVariable( 'action_url', '/explayouts_ui_api/app/api/layouts' );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_create.tpl' );
    return $Result;
}

$locale = eZLocale::currentLocaleCode();
$locale = str_replace( '_', '-', $locale );
$locale = preg_replace( '/^eng\b/', 'en', $locale );

if ( preg_match( '#^preview/(\d+)$#', $apiPath, $m ) )
{
    $layoutId = (int)$m[1];
    $layout = expLayoutsLayout::fetch( $layoutId );
    if ( !$layout )
    {
        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = 'Layout not found.';
        return $Result;
    }

    $zones = array();
    foreach ( expLayoutsZone::fetchByLayout( $layoutId, null ) as $zone )
    {
        $blocks = array();
        foreach ( expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), null ) as $block )
        {
            $blocks[] = array(
                'definition_identifier' => (string)$block->attribute( 'definition_identifier' ),
                'html' => expLayoutsUIApplicationApi::renderBlockHtml( $block ),
            );
        }
        $zones[] = array(
            'identifier' => (string)$zone->attribute( 'identifier' ),
            'blocks' => $blocks,
        );
    }

    $tpl->setVariable( 'layout', $layout );
    $tpl->setVariable( 'zones', $zones );
    $tpl->setVariable( 'locale', $locale );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/preview.tpl' );
    return $Result;
}

if ( $apiPath === 'mappings' )
{
    $ruleService = new expLayoutsCoreRuleService();
    $rules = $ruleService->listAll();
    $counts = array();
    foreach ( $rules as $rule )
    {
        $layoutId = (int)$rule->attribute( 'layout_id' );
        $counts[$layoutId] = isset( $counts[$layoutId] ) ? $counts[$layoutId] + 1 : 1;
    }

    $mappings = array();
    foreach ( $counts as $layoutId => $count )
    {
        $layout = expLayoutsLayout::fetch( $layoutId );
        $mappings[] = array(
            'layout_id' => $layoutId,
            'count' => $count,
            'layout_name' => $layout ? (string)$layout->attribute( 'name' ) : 'Unknown',
        );
    }

    $tpl->setVariable( 'mappings', $mappings );
    $tpl->setVariable( 'locale', $locale );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/mappings.tpl' );
    return $Result;
}

if ( $apiPath === 'rules' )
{
    $ruleService = new expLayoutsCoreRuleService();
    $ruleObjects = $ruleService->listAll();
    $rules = array();
    foreach ( $ruleObjects as $rule )
    {
        $r = expLayoutsUIApplicationApi::ruleToArray( $rule );
        $layout = expLayoutsLayout::fetch( (int)$r['layout_id'] );
        $r['layout_name'] = $layout ? (string)$layout->attribute( 'name' ) : 'Unknown';
        $rules[] = $r;
    }

    $tpl->setVariable( 'rules', $rules );
    $tpl->setVariable( 'locale', $locale );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/rules.tpl' );
    return $Result;
}

if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/edit$#', $apiPath, $m ) )
{
    $locale = $m[1];
    $blockId = (int)$m[2];
    $tpl->setVariable( 'form_url', "/explayouts_ui_api/app/$locale/blocks/$blockId/form" );
    $tpl->setVariable( 'block_id', $blockId );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_edit.tpl' );
    return $Result;
}

if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/form$#', $apiPath, $m ) )
{
    $locale = $m[1];
    $blockId = (int)$m[2];
    $block = expLayoutsBlock::fetch( $blockId );
    if ( !$block )
    {
        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = 'Block not found.';
        return $Result;
    }

    $definitionIdentifier = (string)$block->attribute( 'definition_identifier' );
    $blockInfo = expLayoutsBlockHandlerFactory::getBlockInfo( $definitionIdentifier );
    $viewTypes = is_array( $blockInfo ) && isset( $blockInfo['view_types'] ) ? $blockInfo['view_types'] : array( 'default' );

    $handler = expLayoutsBlockHandlerFactory::get( $definitionIdentifier );
    $parameters = $handler ? $handler->getParameters() : array();

    $parameterValues = array();
    foreach ( expLayoutsBlockParameter::fetchByBlock( $blockId ) as $param )
    {
        $parameterValues[(string)$param->attribute( 'name' )] = (string)$param->attribute( 'value' );
    }

    foreach ( $parameterValues as $paramName => $paramValue )
    {
        if ( !isset( $parameters[$paramName] ) )
        {
            $parameters[$paramName] = array(
                'name' => ucwords( str_replace( array( '_', ':' ), ' ', $paramName ) ),
                'type' => ( strpos( $paramValue, "\n" ) !== false || strlen( $paramValue ) > 120 ) ? 'textarea' : 'text',
            );
        }
    }

    $collectionItems = array();
    $collection = expLayoutsCollection::fetchByBlock( $blockId );
    if ( $collection && (string)$collection->attribute( 'collection_type' ) === 'manual' )
    {
        foreach ( expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) ) as $item )
        {
            $nodeId = (int)$item->attribute( 'value_id' );
            $node = $nodeId > 0 ? eZContentObjectTreeNode::fetch( $nodeId ) : null;
            $collectionItems[] = array(
                'node_id' => $nodeId,
                'parent_node_id' => $node instanceof eZContentObjectTreeNode ? (int)$node->attribute( 'parent_node_id' ) : 0,
                'name' => $node instanceof eZContentObjectTreeNode ? (string)$node->attribute( 'name' ) : 'Unknown node ' . $nodeId,
            );
        }
    }

    $tpl->setVariable( 'block', $block );
    $tpl->setVariable( 'action_url', "/explayouts_ui_api/app/api/$locale/blocks/$blockId" );
    $tpl->setVariable( 'ezxform_token', ezxFormToken::getToken() );
    $tpl->setVariable( 'view_types', $viewTypes );
    $tpl->setVariable( 'parameters', $parameters );
    $tpl->setVariable( 'parameter_values', $parameterValues );
    $tpl->setVariable( 'collection_items', $collectionItems );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_fields.tpl' );
    return $Result;
}

$ini = eZINI::instance( 'explayouts_ui_api.ini' );
$customStylesheets = $ini->hasVariable( 'App', 'Stylesheets' ) ? $ini->variable( 'App', 'Stylesheets' ) : array();
$customJavascripts = $ini->hasVariable( 'App', 'Javascripts' ) ? $ini->variable( 'App', 'Javascripts' ) : array();
$googleMapsApiKey = $ini->hasVariable( 'App', 'GoogleMapsApiKey' ) ? trim( $ini->variable( 'App', 'GoogleMapsApiKey' ) ) : '';

$tpl->setVariable( 'locale', $locale );
$tpl->setVariable( 'route_prefix', '/explayouts_ui_api' );
$tpl->setVariable( 'base_path', '/explayouts_ui_api' );
$tpl->setVariable( 'cb_base_path', '/explayouts_content_browser_ui' );
$tpl->setVariable( 'custom_stylesheets', is_array( $customStylesheets ) ? $customStylesheets : array() );
$tpl->setVariable( 'custom_javascripts', is_array( $customJavascripts ) ? $customJavascripts : array() );
$returnTo = isset( $_GET['return_to'] ) ? $_GET['return_to'] : '';

$tpl->setVariable( 'google_maps_api_key', $googleMapsApiKey );
$tpl->setVariable( 'app_version', '' );
$tpl->setVariable( 'edition', 'Open Source' );
$tpl->setVariable( 'page_title', 'Exponential Layouts' );
$tpl->setVariable( 'ezxform_token', ezxFormToken::getToken() );
$tpl->setVariable( 'return_to', $returnTo );

$Result = array();
$Result['pagelayout'] = false;
$Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/app.tpl' );
return $Result;
