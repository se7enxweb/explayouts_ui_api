<?php
/**
 * The code of extension/explayouts_ui_api/modules/explayouts_ui_api/app.php, moved into a class (#207 stage 1). The file extension/explayouts_ui_api/modules/explayouts_ui_api/app.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi
{

class App extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        // Whether this request would have shown debug output, asked before it is
        // switched off for the page: the API adds an error's details only then.
        $showErrorDetails = \eZDebug::isDebugEnabled();
        \eZDebug::updateSettings( array( 'debug-enabled' => false ) );

        $requestUri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url( $requestUri, PHP_URL_PATH );
        $path = preg_replace( '#^/index\.php/#', '/', $path );
        $path = rtrim( $path, '/' );

        $apiPath = '';
        // A siteaccess reached by path (/admin/explayouts_ui_api/app) puts its
        // prefix in front; the editor's own path is what follows it.
        if ( preg_match( '#^(?:/[^/]+)*?/explayouts_ui_api/app(?:/(.*))?$#', $path, $m ) )
        {
            $apiPath = isset( $m[1] ) ? $m[1] : '';
        }

        // The block definitions of the site the editor previews in, for the whole request (forms, the block list,
        // the checks of a save): in an administration the editor would read the administration's settings, while a
        // design keeps the block settings it adds in its own siteaccess (explayouts' factory, where it has
        // useSiteAccess()). An administration is a siteaccess whose SiteDesign begins with "admin", as
        // expLayoutsPreviewSiteAccess tells them apart; on a public siteaccess the editor keeps its own settings.
        // Only in a request that runs in a siteaccess (the editor's tests run the view without one).
        $access = class_exists( 'eZSiteAccess' ) ? \eZSiteAccess::current() : null;
        if ( is_array( $access ) && method_exists( 'expLayoutsBlockHandlerFactory', 'useSiteAccess' ) && class_exists( 'expLayoutsPreviewSiteAccess' )
             && strpos( (string)\eZINI::instance()->variable( 'DesignSettings', 'SiteDesign' ), 'admin' ) === 0 )
            \expLayoutsBlockHandlerFactory::useSiteAccess( \expLayoutsPreviewSiteAccess::resolve() );

        if ( strpos( $apiPath, 'api/' ) === 0 )
        {
            $parts = explode( '/', substr( $apiPath, 4 ) );
            return $this->viewResult( isset( $Result ) ? $Result : null,  \expLayoutsUIApplicationApi::handle( $parts, $showErrorDetails ) );
        }

        $tpl = \eZTemplate::factory();
        $tpl->resetVariables();

        // Every editor URL the page and its scripts use, with the siteaccess's
        // prefix when it has one: hardcoded, /admin/explayouts_ui_api/app sent its
        // API calls to the public site's /explayouts_ui_api/app/api and got 403.
        $expLayoutsUrl = function ( $url )
        {
            \eZURI::transformURI( $url, false, 'relative', false );
            return $url;
        };

        if ( $apiPath === 'layouts/form/create' )
        {
            $tpl->setVariable( 'layout_types', \expLayoutsLayoutType::getAvailableTypes() );
            $tpl->setVariable( 'action_url', $expLayoutsUrl( '/explayouts_ui_api/app/api/layouts' ) );

            $Result = array();
            $Result['pagelayout'] = false;
            $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_create.tpl' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        $locale = \eZLocale::currentLocaleCode();
        $locale = str_replace( '_', '-', $locale );
        $locale = preg_replace( '/^eng\b/', 'en', $locale );

        if ( preg_match( '#^preview/(\d+)$#', $apiPath, $m ) )
        {
            $layoutId = (int)$m[1];
            $layout = \expLayoutsLayout::fetch( $layoutId );
            if ( !$layout )
            {
                $Result = array();
                $Result['pagelayout'] = false;
                $Result['content'] = 'Layout not found.';
                return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
            }

            $zones = array();
            foreach ( \expLayoutsZone::fetchByLayout( $layoutId, null ) as $zone )
            {
                $blocks = array();
                foreach ( \expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), null ) as $block )
                {
                    $blocks[] = array(
                        'definition_identifier' => (string)$block->attribute( 'definition_identifier' ),
                        'html' => \expLayoutsUIApplicationApi::renderBlockHtml( $block ),
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
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( $apiPath === 'mappings' )
        {
            $ruleService = new \expLayoutsCoreRuleService();
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
                $layout = \expLayoutsLayout::fetch( $layoutId );
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
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( $apiPath === 'rules' )
        {
            $ruleService = new \expLayoutsCoreRuleService();
            $ruleObjects = $ruleService->listAll();
            $rules = array();
            foreach ( $ruleObjects as $rule )
            {
                $r = \expLayoutsUIApplicationApi::ruleToArray( $rule );
                $layout = \expLayoutsLayout::fetch( (int)$r['layout_id'] );
                $r['layout_name'] = $layout ? (string)$layout->attribute( 'name' ) : 'Unknown';
                $rules[] = $r;
            }

            $tpl->setVariable( 'rules', $rules );
            $tpl->setVariable( 'locale', $locale );

            $Result = array();
            $Result['pagelayout'] = false;
            $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/rules.tpl' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/edit$#', $apiPath, $m ) )
        {
            $locale = $m[1];
            $blockId = (int)$m[2];
            $block = \expLayoutsBlock::fetch( $blockId );
            $collection = $block ? \expLayoutsCollection::fetchByBlock( $blockId ) : null;

            if ( $collection )
            {
                $collectionData = \expLayoutsUIApplicationApi::collectionToArray( $collection, $block );
                $tpl->setVariable( 'collection', $collectionData );
                $tpl->setVariable( 'query_form_url', $expLayoutsUrl( "/explayouts_ui_api/app/$locale/blocks/$blockId/collections/default/query/form" ) );
            }
            else
            {
                $tpl->setVariable( 'collection', null );
                $tpl->setVariable( 'query_form_url', '' );
            }

            $tpl->setVariable( 'form_url', $expLayoutsUrl( "/explayouts_ui_api/app/$locale/blocks/$blockId/form" ) );
            $tpl->setVariable( 'content_form_url', $expLayoutsUrl( "/explayouts_ui_api/app/$locale/blocks/$blockId/form/edit/content" ) );
            $tpl->setVariable( 'block_id', $blockId );
            // the Content tab opens first when it has parameters of the block (as the reference's content form)
            $tpl->setVariable( 'has_content_parameters', $block && count( \expLayoutsUIApplicationApi::blockFormParameters( $block, 'content' )['parameters'] ) > 0 );
            $tpl->setVariable( 'block', $block );
            $tpl->setVariable( 'block_name', $block ? (string)$block->attribute( 'name' ) : '' );

            $Result = array();
            $Result['pagelayout'] = false;
            $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_edit.tpl' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/form/edit/content$#', $apiPath, $m ) )
        {
            $locale = $m[1];
            $blockId = (int)$m[2];
            $block = \expLayoutsBlock::fetch( $blockId );
            if ( !$block )
            {
                $Result = array();
                $Result['pagelayout'] = false;
                $Result['content'] = 'Block not found.';
                return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
            }

            $tpl->setVariable( 'action_url', $expLayoutsUrl( "/explayouts_ui_api/app/api/$locale/blocks/$blockId" ) );
            $tpl->setVariable( 'ezxform_token', \ezxFormToken::getToken() );
            $tpl->setVariable( 'block_name', (string)$block->attribute( 'name' ) );
            // the parameters of the Content tab, as the reference's content form: the block's text, links, images,
            // video and map ([BlockDefinition_<id>] ContentParameters[] of explayouts)
            $form = \expLayoutsUIApplicationApi::blockFormParameters( $block, 'content' );
            $tpl->setVariable( 'parameters', $form['parameters'] );
            $tpl->setVariable( 'parameter_values', $form['values'] );

            $Result = array();
            $Result['pagelayout'] = false;
            $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_content.tpl' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/form$#', $apiPath, $m ) )
        {
            $locale = $m[1];
            $blockId = (int)$m[2];
            $block = \expLayoutsBlock::fetch( $blockId );
            if ( !$block )
            {
                $Result = array();
                $Result['pagelayout'] = false;
                $Result['content'] = 'Block not found.';
                return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
            }

            // the parameters of the Design tab: all but the ones of the Content tab (blockFormParameters())
            $form = \expLayoutsUIApplicationApi::blockFormParameters( $block, 'design' );
            $handler = $form['handler'];
            $viewTypes = $form['view_types'];
            $parameters = $form['parameters'];
            $parameterValues = $form['values'];

            $collectionItems = array();
            $collection = \expLayoutsCollection::fetchByBlock( $blockId );
            if ( $collection && (string)$collection->attribute( 'collection_type' ) === 'manual' )
            {
                foreach ( \expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) ) as $item )
                {
                    $nodeId = (int)$item->attribute( 'value_id' );
                    $node = $nodeId > 0 ? \eZContentObjectTreeNode::fetch( $nodeId ) : null;
                    $collectionItems[] = array(
                        'node_id' => $nodeId,
                        'parent_node_id' => $node instanceof \eZContentObjectTreeNode ? (int)$node->attribute( 'parent_node_id' ) : 0,
                        'name' => $node instanceof \eZContentObjectTreeNode ? (string)$node->attribute( 'name' ) : 'Unknown node ' . $nodeId,
                    );
                }
            }

            $tpl->setVariable( 'block', $block );
            $tpl->setVariable( 'block_name', (string)$block->attribute( 'name' ) );
            $tpl->setVariable( 'action_url', $expLayoutsUrl( "/explayouts_ui_api/app/api/$locale/blocks/$blockId" ) );
            $tpl->setVariable( 'ezxform_token', \ezxFormToken::getToken() );
            // The item view types of the definition: the handler's own list and the ones the settings add
            // (ItemViewTypes[] of [BlockDefinition_*]), from explayouts' factory where it has itemViewTypes().
            // $handler is false when the block's definition has no BlockDefinition_*
            // group in explayouts.ini. method_exists() throws on false under PHP 8, so
            // the whole sidebar request died and the editor span forever instead of
            // showing an empty form.
            if ( method_exists( 'expLayoutsBlockHandlerFactory', 'itemViewTypes' ) )
                $itemViewTypes = \expLayoutsBlockHandlerFactory::itemViewTypes( (string)$block->attribute( 'definition_identifier' ) );
            else
                $itemViewTypes = is_object( $handler ) && method_exists( $handler, 'getItemViewTypes' )
                    ? $handler->getItemViewTypes()
                    : array();

            $tpl->setVariable( 'view_types', $viewTypes );
            $tpl->setVariable( 'view_type_names', $form['view_type_names'] );
            $tpl->setVariable( 'item_view_types', $itemViewTypes );
            $tpl->setVariable( 'parameters', $parameters );
            $tpl->setVariable( 'parameter_values', $parameterValues );
            $tpl->setVariable( 'collection_items', $collectionItems );
            $tpl->setVariable( 'collection', $collection );

            $Result = array();
            $Result['pagelayout'] = false;
            $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/form_block_fields.tpl' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        if ( preg_match( '#^([a-zA-Z_]+)/blocks/(\d+)/collections/([^/]+)/query/form$#', $apiPath, $m ) )
        {
            $locale = $m[1];
            $blockId = (int)$m[2];
            $collectionIdentifier = $m[3];

            $block = \expLayoutsBlock::fetch( $blockId );
            $collection = $block ? \expLayoutsCollection::fetchByBlock( $blockId ) : null;
            if ( !$collection )
            {
                $Result = array();
                $Result['pagelayout'] = false;
                $Result['content'] = 'Collection not found.';
                return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
            }

            $collectionId = (int)$collection->attribute( 'id' );
            $query = \expLayoutsCollectionQuery::fetchByCollection( $collectionId, true );
            $queryType = $query ? (string)$query->attribute( 'query_type' ) : 'exponential_content_search';

            $handler = \expLayoutsQueryHandlerFactory::get( $queryType );
            if ( !$handler )
                $handler = \expLayoutsQueryHandlerFactory::get( 'exponential_content_search' );

            // Both lookups can fail, leaving $handler false; guard as above.
            $parameterDefinitions = is_object( $handler ) && method_exists( $handler, 'getParameters' )
                ? $handler->getParameters()
                : array();
            $parameterValues = $query ? @json_decode( (string)$query->attribute( 'parameters' ), true ) : array();
            if ( !is_array( $parameterValues ) )
                $parameterValues = array();

            $defaults = array();
            foreach ( $parameterDefinitions as $name => $definition )
                $defaults[$name] = isset( $definition['default'] ) ? $definition['default'] : '';
            $parameterValues = array_merge( $defaults, $parameterValues );

            $queryTree = \expLayoutsUIApplicationApi::buildQueryParameterTree( $handler );

            $tpl->setVariable( 'action_url', $expLayoutsUrl( "/explayouts_ui_api/app/api/$locale/blocks/$blockId/collections/$collectionIdentifier/query" ) );
            $tpl->setVariable( 'ezxform_token', \ezxFormToken::getToken() );
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
            return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );
        }

        $ini = \eZINI::instance( 'explayouts_ui_api.ini' );
        $customStylesheets = $ini->hasVariable( 'App', 'Stylesheets' ) ? $ini->variable( 'App', 'Stylesheets' ) : array();
        $customJavascripts = $ini->hasVariable( 'App', 'Javascripts' ) ? $ini->variable( 'App', 'Javascripts' ) : array();
        $googleMapsApiKey = $ini->hasVariable( 'App', 'GoogleMapsApiKey' ) ? trim( $ini->variable( 'App', 'GoogleMapsApiKey' ) ) : '';

        $tpl->setVariable( 'locale', $locale );
        $tpl->setVariable( 'route_prefix', $expLayoutsUrl( '/explayouts_ui_api' ) );
        $tpl->setVariable( 'base_path', $expLayoutsUrl( '/explayouts_ui_api' ) );
        $tpl->setVariable( 'cb_base_path', $expLayoutsUrl( '/explayouts_content_browser_ui' ) );
        $tpl->setVariable( 'custom_stylesheets', is_array( $customStylesheets ) ? $customStylesheets : array() );
        $tpl->setVariable( 'custom_javascripts', is_array( $customJavascripts ) ? $customJavascripts : array() );
        // a path of this site only: the editor returns there, also by location.replace() as it loads
        $returnTo = \expLayoutsUIApplicationApi::safeReturnTo( isset( $_GET['return_to'] ) ? $_GET['return_to'] : '' );

        $tpl->setVariable( 'google_maps_api_key', $googleMapsApiKey );
        // app_version and edition are joined into #app[data-version], which the SPA
        // reads into the app-logo tooltip.
        $tpl->setVariable( 'app_version', '' );
        $tpl->setVariable( 'edition', 'Exponential Layouts' );
        $tpl->setVariable( 'page_title', 'Exponential Layouts' );
        $tpl->setVariable( 'ezxform_token', \ezxFormToken::getToken() );
        $tpl->setVariable( 'return_to', $returnTo );

        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = $tpl->fetch( 'design:explayouts_ui_api/app.tpl' );
        return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
