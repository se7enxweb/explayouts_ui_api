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
$tpl->resetVariables();

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
    $block = expLayoutsBlock::fetch( $blockId );
    $collection = $block ? expLayoutsCollection::fetchByBlock( $blockId ) : null;

    if ( $collection )
    {
        $collectionData = expLayoutsUIApplicationApi::collectionToArray( $collection, $block );
        $tpl->setVariable( 'collection', $collectionData );
        $tpl->setVariable( 'query_form_url', "/explayouts_ui_api/app/$locale/blocks/$blockId/collections/default/query/form" );
    }
    else
    {
        $tpl->setVariable( 'collection', null );
        $tpl->setVariable( 'query_form_url', '' );
    }

    $tpl->setVariable( 'form_url', "/explayouts_ui_api/app/$locale/blocks/$blockId/form" );
    $tpl->setVariable( 'content_form_url', "/explayouts_ui_api/app/$locale/blocks/$blockId/form/edit/content" );
    $tpl->setVariable( 'block_id', $blockId );
    $tpl->setVariable( 'block', $block );
    $tpl->setVariable( 'block_name', $block ? (string)$block->attribute( 'name' ) : '' );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_edit.tpl' );
    return $Result;
}

if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/form/edit/content$#', $apiPath, $m ) )
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

    $tpl->setVariable( 'action_url', "/explayouts_ui_api/app/api/$locale/blocks/$blockId" );
    $tpl->setVariable( 'ezxform_token', ezxFormToken::getToken() );
    $tpl->setVariable( 'block_name', (string)$block->attribute( 'name' ) );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_content.tpl' );
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
    foreach ( $parameters as $paramName => $paramDef )
    {
        $parameterValues[$paramName] = isset( $paramDef['default'] ) ? $paramDef['default'] : '';
    }
    foreach ( expLayoutsBlockParameter::fetchByBlock( $blockId ) as $param )
    {
        $parameterValues[(string)$param->attribute( 'name' )] = (string)$param->attribute( 'value' );
    }

    // Drop legacy query parameters from the design form; they are handled by
    // the Content tab query builder or block fetchItems, not by design settings.
    $legacyQueryParams = array( 'query_type', 'parent_node_id', 'node_id', 'limit', 'offset', 'class_filter', 'sort' );
    foreach ( $legacyQueryParams as $legacy )
    {
        unset( $parameterValues[$legacy] );
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
    $itemViewTypes = method_exists( $handler, 'getItemViewTypes' ) ? $handler->getItemViewTypes() : array();

    $tpl->setVariable( 'view_types', $viewTypes );
    $tpl->setVariable( 'item_view_types', $itemViewTypes );
    $tpl->setVariable( 'parameters', $parameters );
    $tpl->setVariable( 'parameter_values', $parameterValues );
    $tpl->setVariable( 'collection_items', $collectionItems );
    $tpl->setVariable( 'collection', $collection );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_fields.tpl' );
    return $Result;
}

if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/collections/([^/]+)/query/form$#', $apiPath, $m ) )
{
    $locale = $m[1];
    $blockId = (int)$m[2];
    $collectionIdentifier = $m[3];

    $block = expLayoutsBlock::fetch( $blockId );
    $collection = $block ? expLayoutsCollection::fetchByBlock( $blockId ) : null;
    if ( !$collection )
    {
        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = 'Collection not found.';
        return $Result;
    }

    $collectionId = (int)$collection->attribute( 'id' );
    $query = expLayoutsCollectionQuery::fetchByCollection( $collectionId, true );
    $queryType = $query ? (string)$query->attribute( 'query_type' ) : 'ibexa_content_search';

    $handler = expLayoutsQueryHandlerFactory::get( $queryType );
    if ( !$handler )
        $handler = expLayoutsQueryHandlerFactory::get( 'ibexa_content_search' );

    $parameterDefinitions = method_exists( $handler, 'getParameters' ) ? $handler->getParameters() : array();
    $parameterValues = $query ? @json_decode( (string)$query->attribute( 'parameters' ), true ) : array();
    if ( !is_array( $parameterValues ) )
        $parameterValues = array();

    $defaults = array();
    foreach ( $parameterDefinitions as $name => $definition )
        $defaults[$name] = isset( $definition['default'] ) ? $definition['default'] : '';
    $parameterValues = array_merge( $defaults, $parameterValues );

    $queryTree = expLayoutsUIApplicationApi::buildQueryParameterTree( $handler );

    $tpl->setVariable( 'action_url', "/explayouts_ui_api/app/api/$locale/blocks/$blockId/collections/$collectionIdentifier/query" );
    $tpl->setVariable( 'ezxform_token', ezxFormToken::getToken() );
    $tpl->setVariable( 'query_type', $queryType );
    $tpl->setVariable( 'parameters', $queryTree['tree'] );
    $tpl->setVariable( 'basic_params', $queryTree['basic'] );
    $tpl->setVariable( 'advanced_params', $queryTree['advanced'] );
    $tpl->setVariable( 'parameter_values', $parameterValues );
    $tpl->setVariable( 'offset', (int)$collection->attribute( 'offset_value' ) );
    $tpl->setVariable( 'limit', (int)$collection->attribute( 'limit_value' ) );

    $Result = array();
    $Result['pagelayout'] = false;
    $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_query_edit.tpl' );
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
