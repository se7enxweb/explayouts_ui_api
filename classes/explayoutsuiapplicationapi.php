<?php
class expLayoutsUIApplicationApi
{
    public static function handle( $parts )
    {
        eZDebug::updateSettings( array( 'debug-enabled' => false ) );
        eZDebug::setHandleType( eZDebug::HANDLE_NONE );
        header( 'Content-Type: application/json' );

        try
        {
            $resource = isset( $parts[0] ) ? $parts[0] : '';

            if ( $resource === 'config' )
                return self::handleConfig( array_slice( $parts, 1 ) );

            $layoutsIndex = array_search( 'layouts', $parts );
            $blocksIndex = array_search( 'blocks', $parts );

            if ( $layoutsIndex !== false )
            {
                $layoutSegment = isset( $parts[$layoutsIndex + 1] ) ? $parts[$layoutsIndex + 1] : '';
                if ( $layoutSegment === 'shared' )
                    return self::handleSharedLayouts();

                $subResource = isset( $parts[$layoutsIndex + 2] ) ? $parts[$layoutsIndex + 2] : '';
                if ( $subResource === 'blocks' )
                    return self::handleBlocks( $parts, $layoutsIndex );
                if ( $subResource === 'zones' )
                    return self::handleZones( $parts, $layoutsIndex );

                return self::handleLayouts( $parts, $layoutsIndex );
            }

            if ( $blocksIndex !== false )
            {
                return self::handleBlock( $parts, $blocksIndex );
            }

            if ( $resource === 'rules' )
                return self::handleRules( array_slice( $parts, 1 ) );

            if ( $resource === 'mappings' )
                return self::handleMappings( array_slice( $parts, 1 ) );

            if ( $resource === 'transfer' )
                return self::handleTransfer( array_slice( $parts, 1 ) );

            if ( $resource === 'collections' )
                return self::handleCollections( array_slice( $parts, 1 ) );

            if ( $resource === 'content_browser' )
                return self::handleContentBrowser( array_slice( $parts, 1 ) );

            if ( $resource === 'forms' )
                return self::handleForms( array_slice( $parts, 1 ) );

            if ( $resource === 'parameters' )
                return self::handleParameters( array_slice( $parts, 1 ) );

            if ( $resource === 'versions' )
                return self::handleVersions( array_slice( $parts, 1 ) );

            if ( $resource === 'share' )
                return self::handleShare( array_slice( $parts, 1 ) );

            return self::response( array( 'error' => 'Unknown resource.' ), 404 );
        }
        catch ( Throwable $e )
        {
            $server = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '?';
            $uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
            eZLog::write( 'expLayoutsUIApplicationApi fatal (' . $server . ' ' . $uri . '): ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString(), 'error.log' );
            return self::response( array(
                'error' => 'Internal error',
                'details' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
            ), 500 );
        }
    }

    protected static function loadLayoutForApi( $service, $layoutId, $fallback = 'load' )
    {
        $published = isset( $_GET['published'] ) ? (string)$_GET['published'] : '';
        if ( $published === 'false' )
            return $service->loadDraft( $layoutId );
        if ( $published === 'true' )
            return $service->loadPublished( $layoutId );
        if ( $fallback === 'loadDraft' )
            return $service->loadDraft( $layoutId );
        if ( $fallback === 'loadPublished' )
            return $service->loadPublished( $layoutId );
        return $service->load( $layoutId );
    }

    protected static function handleConfig( $parts )
    {
        $sub = isset( $parts[0] ) ? $parts[0] : '';
        if ( $sub === 'layout_types' )
            return self::layoutTypes();
        if ( $sub === 'block_types' )
            return self::blockTypes();
        return self::config();
    }

    protected static function config()
    {
        return self::response( array(
            'csrf_token' => ezxFormToken::getToken(),
            'automatic_cache_clear' => true,
            'edition' => 'Open Source',
        ) );
    }

    protected static function layoutTypes()
    {
        $types = array();
        foreach ( expLayoutsLayoutType::getAvailableTypes() as $type )
        {
            $info = expLayoutsLayoutType::getTypeInfo( $type['identifier'] );
            $zones = array();
            if ( $info && is_array( $info['zones'] ) )
            {
                foreach ( $info['zones'] as $zoneId )
                {
                    $zones[] = array( 'identifier' => $zoneId, 'name' => $zoneId );
                }
            }
            $types[] = array(
                'identifier' => $info['identifier'],
                'name' => $info['name'],
                'zones' => $zones,
            );
        }
        return self::response( array( 'values' => $types, 'total' => count( $types ) ) );
    }

    /**
     * The editor's icon for each block type.
     *
     * The block_types/item template renders an <img> when a block type
     * declares an icon and falls back to a CSS font glyph when it does not.
     * The font only maps a glyph for eighteen of the forty types offered, so
     * anything left undeclared drew the font's default box - which is what
     * made the plus menu look nothing like the reference installation.
     *
     * The files are the reference installation's own icons, copied into this
     * extension's design by
     * ai/bin/one/copy_nexus_block_icons_into_explayouts_ui_api.sh. Three
     * identifiers are named differently here than upstream: video is
     * external_video, html is html_snippet, and tpl_block is twig_block.
     */
    const BLOCK_ICON_BASE = '/extension/explayouts_ui_api/design/standard/images/';

    protected static function blockIconMap()
    {
        return array(
            // Basic
            'title' => 'blocks/icon-title.svg',
            'text' => 'blocks/icon-text.svg',
            'button' => 'blocks/icon-button.svg',
            'rich_text' => 'blocks/icon-rich_text.svg',
            'markdown' => 'blocks/icon-markdown.svg',
            'map' => 'blocks/icon-map.svg',
            'html' => 'blocks/icon-html_snippet.svg',
            'video' => 'blocks/icon-external_video.svg',

            // Listing
            'list' => 'blocks/icon-list.svg',
            'grid' => 'blocks/icon-grid.svg',
            'list_zigzag' => 'blocks/icon-list-zig-zag.svg',
            'list_accordion' => 'blocks/icon-list-accordion.svg',

            // Gallery
            'gallery' => 'blocks/icon-gallery.svg',
            'slider' => 'blocks/icon-slider.svg',
            'thumb_gallery' => 'blocks/icon-thumb_gallery.svg',
            'grid_gallery' => 'blocks/icon-grid_gallery.svg',
            'sushi_bar' => 'blocks/icon-sushi_bar.svg',

            // Containers
            'column' => 'blocks/icon-column.svg',
            'two_columns' => 'blocks/icon-two_columns.svg',
            'three_columns' => 'blocks/icon-three_columns.svg',
            'four_columns' => 'blocks/icon-four_columns.svg',

            // Placeholders
            'full_view' => 'blocks/icon-full_view.svg',
            'tpl_block' => 'blocks/icon-twig_block.svg',

            // Components, and the ibexa_component_* twins of the same thing
            'about' => 'components/icon-component-about.svg',
            'features' => 'components/icon-component-features.svg',
            'hero' => 'components/icon-component-hero.svg',
            'lead' => 'components/icon-component-lead.svg',
            'logos' => 'components/icon-component-logos.svg',
            'quote' => 'components/icon-component-quote.svg',
            'ibexa_component_about' => 'components/icon-component-about.svg',
            'ibexa_component_features' => 'components/icon-component-features.svg',
            'ibexa_component_hero' => 'components/icon-component-hero.svg',
            'ibexa_component_lead' => 'components/icon-component-lead.svg',
            'ibexa_component_logos' => 'components/icon-component-logos.svg',
            'ibexa_component_quote' => 'components/icon-component-quote.svg',
        );
    }

    /**
     * Blocks this installation offers that the reference installation has no
     * icon for. They take the upstream generic component icon rather than a
     * borrowed one, so the menu stays legible without implying a block is
     * something it is not.
     */
    const BLOCK_ICON_FALLBACK = 'components/icon-component-generic.svg';

    protected static function blockIcon( $identifier )
    {
        $map = self::blockIconMap();
        $file = isset( $map[$identifier] ) ? $map[$identifier] : self::BLOCK_ICON_FALLBACK;
        return self::BLOCK_ICON_BASE . $file;
    }

    protected static function blockTypes()
    {
        $groupNames = array(
            'basic' => 'Basic',
            'components' => 'Components',
            'listing' => 'Listing',
            'gallery' => 'Gallery',
            'containers' => 'Containers',
            'placeholders' => 'Placeholders',
            'standard' => 'Standard',
        );

        $groupBlockTypes = array();
        $groups = array();
        $blockTypes = array();

        foreach ( expLayoutsBlockHandlerFactory::getAvailableBlocks() as $identifier )
        {
            $info = expLayoutsBlockHandlerFactory::getBlockInfo( $identifier );
            if ( !$info )
                continue;

            $category = !empty( $info['category'] ) ? (string)$info['category'] : 'standard';
            $groupName = isset( $groupNames[$category] ) ? $groupNames[$category] : ucwords( str_replace( '_', ' ', $category ) );

            if ( !isset( $groups[$category] ) )
            {
                $groups[$category] = array(
                    'identifier' => $category,
                    'name' => $groupName,
                    'enabled' => true,
                    'block_types' => array(),
                );
            }

            $groups[$category]['block_types'][] = (string)$identifier;
            $blockTypes[] = array(
                'identifier' => (string)$identifier,
                'name' => (string)$info['name'],
                'definition_identifier' => (string)$identifier,
                'group_name' => $category,
                'enabled' => true,
                'is_container' => !empty( $info['is_container'] ),
                'icon' => self::blockIcon( (string)$identifier ),
                'parameters' => '{}',
                'defaults' => array(),
            );
        }

        $orderedGroups = array();
        foreach ( array_keys( $groupNames ) as $category )
        {
            if ( isset( $groups[$category] ) )
                $orderedGroups[] = $groups[$category];
        }

        foreach ( $groups as $category => $group )
        {
            if ( !in_array( $category, array_keys( $groupNames ), true ) )
                $orderedGroups[] = $group;
        }

        return self::response( array(
            'block_types' => $blockTypes,
            'block_type_groups' => $orderedGroups,
        ) );
    }

    protected static function handleLayouts( $parts, $layoutsIndex = 0 )
    {
        $id = isset( $parts[$layoutsIndex + 1] ) ? (int)$parts[$layoutsIndex + 1] : 0;
        $sub = isset( $parts[$layoutsIndex + 2] ) ? $parts[$layoutsIndex + 2] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
        $service = new expLayoutsCoreLayoutService();

        if ( $method === 'POST' && $id === 0 )
        {
            $data = self::requestData();
            $identifier = isset( $data['identifier'] ) ? trim( $data['identifier'] ) : '';
            $name = isset( $data['name'] ) ? trim( $data['name'] ) : '';
            $layoutType = isset( $data['layout_type'] ) ? trim( $data['layout_type'] ) : '';

            if ( $layoutType === '' )
                return self::response( array( 'error' => 'Layout type is required.' ), 422 );

            if ( $identifier === '' )
                $identifier = self::slugify( $name !== '' ? $name : $layoutType );

            if ( $name === '' )
                $name = $identifier;

            $layout = $service->create( $identifier, $name, $layoutType );
            return self::response( self::layoutToArray( $layout ), 201 );
        }

        if ( $id > 0 && $sub === 'publish' && $method === 'POST' )
        {
            $layout = $service->publish( $id );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found or could not be published.' ), 404 );
            return self::response( self::layoutToArray( $layout ) );
        }

        if ( $id > 0 && $sub === 'draft' && $method === 'DELETE' )
        {
            $layout = $service->discard( $id );
            return self::response( self::layoutToArray( $layout ), 200 );
        }

        if ( $id > 0 && $sub === 'draft' && $method === 'POST' )
        {
            $layout = $service->createDraft( $id );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );
            return self::response( self::layoutToArray( $layout ), 201 );
        }

        if ( $id > 0 )
        {
            $layout = self::loadLayoutForApi( $service, $id );
            return self::response( $layout ? self::layoutToArray( $layout ) : array( 'error' => 'Layout not found.' ), $layout ? 200 : 404 );
        }

        $layouts = $service->listAll();
        return self::response( array( 'values' => array_map( array( __CLASS__, 'layoutToArray' ), $layouts ), 'total' => count( $layouts ) ) );
    }

    /**
     * GET layouts/shared - the layouts a zone may be linked to.
     *
     * Only published shared layouts: a zone links to shared content that is
     * live, never to somebody's unpublished draft of it.
     */
    protected static function handleSharedLayouts()
    {
        $layouts = expLayoutsLayout::fetchShared( 2 );
        return self::response( array(
            'values' => array_map( array( __CLASS__, 'layoutToArray' ), $layouts ),
            'total' => count( $layouts ),
        ) );
    }

    /**
     * Zone-scoped routes:
     *   GET    [:locale/]layouts/:id/zones/:identifier/blocks
     *   POST   layouts/:id/zones/:identifier/link
     *   DELETE layouts/:id/zones/:identifier/link
     */
    protected static function handleZones( $parts, $layoutsIndex )
    {
        $layoutId = isset( $parts[$layoutsIndex + 1] ) ? (int)$parts[$layoutsIndex + 1] : 0;
        $zoneIdentifier = isset( $parts[$layoutsIndex + 3] ) ? trim( $parts[$layoutsIndex + 3] ) : '';
        $action = isset( $parts[$layoutsIndex + 4] ) ? $parts[$layoutsIndex + 4] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( $layoutId <= 0 || $zoneIdentifier === '' )
            return self::response( array( 'error' => 'Missing layout id or zone identifier.' ), 400 );

        $service = new expLayoutsCoreLayoutService();
        $zoneService = new expLayoutsCoreZoneService();

        if ( $action === 'blocks' && $method === 'GET' )
        {
            // The zone whose blocks are being asked for is a zone of the
            // shared layout, so it is read straight off the published row.
            // A bare array, not the usual values/total envelope: the editor
            // plucks the ids straight off this response to remember which
            // blocks belong to the linked zone.
            $layout = self::loadLayoutForApi( $service, $layoutId, 'loadPublished' );
            if ( !$layout )
                return self::response( array() );

            $zone = expLayoutsZone::fetchByLayoutAndIdentifier(
                (int)$layout->attribute( 'id' ), $zoneIdentifier, (int)$layout->attribute( 'status' ) );
            if ( !$zone )
                $zone = expLayoutsZone::fetchByLayoutAndIdentifier( (int)$layout->attribute( 'id' ), $zoneIdentifier, null );
            if ( !$zone )
                return self::response( array() );

            $blocks = self::fetchTopLevelBlocksForZone( $zone );
            return self::response( array_values( array_map( array( __CLASS__, 'blockToArray' ), $blocks ) ) );
        }

        if ( $action !== 'link' )
            return self::response( array( 'error' => 'Unknown zone action.' ), 404 );

        // Linking is an edit, so it happens on the draft like every other one.
        $layout = self::loadLayoutForApi( $service, $layoutId, 'loadDraft' );
        if ( !$layout )
            return self::response( array( 'error' => 'Layout not found.' ), 404 );

        $zone = expLayoutsZone::fetchByLayoutAndIdentifier(
            (int)$layout->attribute( 'id' ), $zoneIdentifier, (int)$layout->attribute( 'status' ) );
        if ( !$zone )
            return self::response( array( 'error' => 'Zone not found.' ), 404 );

        if ( $method === 'DELETE' )
        {
            $zone = $zoneService->unlink( (int)$zone->attribute( 'id' ) );
            return self::response( self::zoneToArray( $zone ) );
        }

        if ( $method === 'POST' || $method === 'PATCH' || $method === 'PUT' )
        {
            $data = self::requestData();
            $linkedLayoutId = isset( $data['linked_layout_id'] ) ? (int)$data['linked_layout_id'] : 0;
            $linkedZoneIdentifier = isset( $data['linked_zone_identifier'] ) ? trim( $data['linked_zone_identifier'] ) : '';

            $linked = $zoneService->link( (int)$zone->attribute( 'id' ), $linkedLayoutId, $linkedZoneIdentifier );
            if ( !$linked )
                return self::response( array( 'error' => 'Zone could not be linked to that layout zone.' ), 422 );

            return self::response( self::zoneToArray( $linked ) );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
    }

    protected static function zoneToArray( $zone )
    {
        $blockIds = array();
        foreach ( self::fetchOwnTopLevelBlocksForZone( $zone ) as $block )
            $blockIds[] = (int)$block->attribute( 'id' );

        return array(
            'identifier' => (string)$zone->attribute( 'identifier' ),
            'name' => (string)$zone->attribute( 'identifier' ),
            'layout_id' => (int)$zone->attribute( 'layout_id' ),
            'block_ids' => $blockIds,
            'allowed_block_definitions' => true,
            'linked_layout_id' => $zone->isLinked() ? self::publishedLayoutId( (int)$zone->attribute( 'linked_layout_id' ) ) : null,
            'linked_zone_identifier' => $zone->isLinked() ? (string)$zone->attribute( 'linked_zone_identifier' ) : null,
        );
    }

    protected static function handleBlocks( $parts, $layoutsIndex )
    {
        $layoutId = isset( $parts[$layoutsIndex + 1] ) ? (int)$parts[$layoutsIndex + 1] : 0;
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( $method === 'POST' )
        {
            $zone = isset( $_POST['zone_identifier'] ) ? trim( $_POST['zone_identifier'] ) : '';
            $definitionIdentifier = isset( $_POST['definition_identifier'] ) ? trim( $_POST['definition_identifier'] ) : '';

            if ( $definitionIdentifier === '' )
                return self::response( array( 'error' => 'Block type is required.' ), 422 );

            $service = new expLayoutsCoreLayoutService();
            $layout = self::loadLayoutForApi( $service, $layoutId, 'loadDraft' );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );
            $layoutId = (int)$layout->attribute( 'id' );

            $parentBlockId = isset( $_POST['parent_block_id'] ) ? (int)$_POST['parent_block_id'] : 0;
            $parentPlaceholder = isset( $_POST['parent_placeholder'] ) ? trim( $_POST['parent_placeholder'] ) : '';

            $zoneObject = null;
            if ( $parentBlockId > 0 )
            {
                $parentBlock = expLayoutsBlock::fetch( $parentBlockId );
                if ( $parentBlock )
                {
                    $zoneObject = expLayoutsZone::fetch( (int)$parentBlock->attribute( 'zone_id' ) );
                    $layoutId = (int)$parentBlock->attribute( 'layout_id' );
                }
            }

            if ( !$zoneObject && $zone !== '' )
            {
                foreach ( expLayoutsZone::fetchByLayout( $layoutId, null ) as $z )
                {
                    if ( (string)$z->attribute( 'identifier' ) === $zone )
                    {
                        $zoneObject = $z;
                        break;
                    }
                }
            }

            if ( !$zoneObject )
                return self::response( array( 'error' => 'Zone not found.' ), 404 );

            if ( $zoneObject->isLinked() )
                return self::response( array( 'error' => 'This zone is linked to a shared layout and cannot take blocks of its own.' ), 403 );

            $block = expLayoutsBlock::create(
                (int)$zoneObject->attribute( 'id' ),
                $layoutId,
                $definitionIdentifier,
                isset( $_POST['name'] ) ? trim( $_POST['name'] ) : ''
            );
            $requestedPosition = isset( $_POST['parent_position'] ) ? (int)$_POST['parent_position'] : null;
            $block->setAttribute( 'position', $requestedPosition === null ? 0 : $requestedPosition );
            if ( $parentBlockId > 0 )
            {
                $block->setAttribute( 'parent_id', $parentBlockId );
                $block->setAttribute( 'placeholder', $parentPlaceholder );
            }
            $block->store();

            self::placeBlockAt( $block, $requestedPosition === null ? PHP_INT_MAX : $requestedPosition );

            return self::response( self::blockToArray( $block ), 201 );
        }

        $service = new expLayoutsCoreLayoutService();
        $layout = self::loadLayoutForApi( $service, $layoutId, 'loadDraft' );
        if ( !$layout )
            return self::response( array( 'values' => array(), 'total' => 0 ) );

        // Only the layout's own blocks. The editor asks for the blocks behind
        // a linked zone separately, against the shared layout they belong to.
        $zoneBlocks = array();
        $layoutId = (int)$layout->attribute( 'id' );
        $zones = expLayoutsZone::fetchByLayout( $layoutId, null );
        foreach ( $zones as $zone )
        {
            foreach ( self::fetchOwnTopLevelBlocksForZone( $zone ) as $block )
            {
                $zoneBlocks[] = $block;
            }
        }

        return self::response( array( 'values' => array_map( array( __CLASS__, 'blockToArray' ), $zoneBlocks ), 'total' => count( $zoneBlocks ) ) );
    }

    protected static function handleBlock( $parts, $blocksIndex )
    {
        $id = isset( $parts[$blocksIndex + 1] ) ? (int)$parts[$blocksIndex + 1] : 0;
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( $method === 'POST' && $id === 0 )
        {
            $data = self::requestData();
            $layoutId = isset( $data['layout_id'] ) ? (int)$data['layout_id'] : 0;
            $zoneIdentifier = isset( $data['zone_identifier'] ) ? trim( $data['zone_identifier'] ) : '';
            $definitionIdentifier = isset( $data['definition_identifier'] ) ? trim( $data['definition_identifier'] ) : '';

            if ( $layoutId === 0 || $zoneIdentifier === '' || $definitionIdentifier === '' )
                return self::response( array( 'error' => 'Missing required block fields.' ), 422 );

            $service = new expLayoutsCoreLayoutService();
            $layout = self::loadLayoutForApi( $service, $layoutId, 'loadDraft' );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );
            $layoutId = (int)$layout->attribute( 'id' );

            $zoneObject = null;
            foreach ( expLayoutsZone::fetchByLayout( $layoutId, null ) as $z )
            {
                if ( (string)$z->attribute( 'identifier' ) === $zoneIdentifier )
                {
                    $zoneObject = $z;
                    break;
                }
            }
            if ( !$zoneObject )
                return self::response( array( 'error' => 'Zone not found.' ), 404 );

            if ( $zoneObject->isLinked() )
                return self::response( array( 'error' => 'This zone is linked to a shared layout and cannot take blocks of its own.' ), 403 );

            $parentBlockId = isset( $data['parent_block_id'] ) ? (int)$data['parent_block_id'] : 0;
            $parentPlaceholder = isset( $data['parent_placeholder'] ) ? trim( $data['parent_placeholder'] ) : '';

            if ( $parentBlockId > 0 )
            {
                $parentBlock = expLayoutsBlock::fetch( $parentBlockId );
                if ( $parentBlock )
                {
                    $zoneObject = expLayoutsZone::fetch( (int)$parentBlock->attribute( 'zone_id' ) );
                    $layoutId = (int)$parentBlock->attribute( 'layout_id' );
                }
            }

            $block = expLayoutsBlock::create(
                $zoneObject ? (int)$zoneObject->attribute( 'id' ) : 0,
                $layoutId,
                $definitionIdentifier,
                isset( $data['name'] ) ? trim( $data['name'] ) : ''
            );
            $requestedPosition = isset( $data['parent_position'] ) ? (int)$data['parent_position'] : null;
            $block->setAttribute( 'status', (int)$layout->attribute( 'status' ) );
            $block->setAttribute( 'position', $requestedPosition === null ? 0 : $requestedPosition );
            if ( $parentBlockId > 0 )
            {
                $block->setAttribute( 'parent_id', $parentBlockId );
                $block->setAttribute( 'placeholder', $parentPlaceholder );
            }
            $block->store();

            self::placeBlockAt( $block, $requestedPosition === null ? PHP_INT_MAX : $requestedPosition );

            self::saveBlockParameters( $block, isset( $data['parameters'] ) && is_array( $data['parameters'] ) ? $data['parameters'] : array() );

            return self::response( self::blockToArray( $block ), 201 );
        }

        $sub = isset( $parts[$blocksIndex + 2] ) ? $parts[$blocksIndex + 2] : '';

        if ( $id > 0 && $sub === 'collections' )
        {
            return self::handleBlockCollections( $parts, $blocksIndex );
        }

        if ( $id > 0 )
        {
            $block = expLayoutsBlock::fetch( $id );
            if ( !$block )
                return self::response( array( 'error' => 'Block not found.' ), 404 );

            if ( $method !== 'GET' && self::blockIsInherited( $block ) )
            {
                return self::response( array(
                    'error' => 'This block belongs to a shared layout and cannot be edited from a layout that links to it.',
                ), 403 );
            }

            if ( $method === 'DELETE' )
            {
                $originZoneId = (int)$block->attribute( 'zone_id' );
                $originParentId = (int)$block->attribute( 'parent_id' );
                $originPlaceholder = $block->attribute( 'placeholder' );
                $blockStatus = (int)$block->attribute( 'status' );

                self::deleteBlock( $block );
                self::compactSiblings( $originZoneId, $originParentId, $originPlaceholder, $blockStatus );

                return self::response( array( 'id' => $id ), 200 );
            }

            if ( $method === 'POST' && $sub === 'copy' )
            {
                $data = self::requestData();
                $parentBlockId = isset( $data['parent_block_id'] ) ? (int)$data['parent_block_id'] : 0;
                $parentPlaceholder = isset( $data['parent_placeholder'] ) ? trim( $data['parent_placeholder'] ) : '';

                $newZoneId = (int)$block->attribute( 'zone_id' );
                $newLayoutId = (int)$block->attribute( 'layout_id' );
                if ( $parentBlockId > 0 )
                {
                    $parentBlock = expLayoutsBlock::fetch( $parentBlockId );
                    if ( $parentBlock )
                    {
                        $newZoneId = (int)$parentBlock->attribute( 'zone_id' );
                        $newLayoutId = (int)$parentBlock->attribute( 'layout_id' );
                    }
                }

                $newBlock = expLayoutsBlock::create(
                    $newZoneId,
                    $newLayoutId,
                    (string)$block->attribute( 'definition_identifier' ),
                    (string)$block->attribute( 'name' )
                );
                $requestedPosition = isset( $data['parent_position'] )
                    ? (int)$data['parent_position']
                    : (int)$block->attribute( 'position' ) + 1;
                $newBlock->setAttribute( 'view_type', (string)$block->attribute( 'view_type' ) );
                $newBlock->setAttribute( 'position', $requestedPosition );
                $newBlock->setAttribute( 'status', (int)$block->attribute( 'status' ) );
                if ( $parentBlockId > 0 )
                {
                    $newBlock->setAttribute( 'parent_id', $parentBlockId );
                    $newBlock->setAttribute( 'placeholder', $parentPlaceholder );
                }
                $newBlock->store();

                self::placeBlockAt( $newBlock, $requestedPosition );

                foreach ( expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) ) as $param )
                {
                    expLayoutsBlockParameter::set( (int)$newBlock->attribute( 'id' ), (string)$param->attribute( 'name' ), (string)$param->attribute( 'value' ) );
                }

                return self::response( self::blockToArray( $newBlock ), 201 );
            }

            if ( $method === 'POST' && $sub === 'move' )
            {
                $data = self::requestData();

                // Remember where the block came from so the gap it leaves
                // behind can be closed once it has been stored elsewhere.
                $originZoneId = (int)$block->attribute( 'zone_id' );
                $originParentId = (int)$block->attribute( 'parent_id' );
                $originPlaceholder = $block->attribute( 'placeholder' );
                $blockStatus = (int)$block->attribute( 'status' );

                if ( isset( $data['zone_identifier'] ) && isset( $data['layout_id'] ) )
                {
                    $zone = self::findZoneByIdentifier( (int)$data['layout_id'], trim( $data['zone_identifier'] ) );
                    if ( $zone )
                    {
                        $block->setAttribute( 'layout_id', (int)$data['layout_id'] );
                        $block->setAttribute( 'zone_id', (int)$zone->attribute( 'id' ) );
                    }
                }

                $requestedPosition = null;
                if ( isset( $data['parent_position'] ) )
                    $requestedPosition = (int)$data['parent_position'];
                elseif ( isset( $data['position'] ) )
                    $requestedPosition = (int)$data['position'];

                if ( $requestedPosition !== null )
                    $block->setAttribute( 'position', $requestedPosition );

                if ( isset( $data['parent_block_id'] ) )
                {
                    $parentBlockId = (int)$data['parent_block_id'];
                    if ( $parentBlockId > 0 )
                    {
                        $block->setAttribute( 'parent_id', $parentBlockId );
                        $block->setAttribute( 'placeholder', isset( $data['parent_placeholder'] ) ? trim( $data['parent_placeholder'] ) : '' );

                        $parentBlock = expLayoutsBlock::fetch( $parentBlockId );
                        if ( $parentBlock )
                        {
                            $block->setAttribute( 'zone_id', (int)$parentBlock->attribute( 'zone_id' ) );
                            $block->setAttribute( 'layout_id', (int)$parentBlock->attribute( 'layout_id' ) );
                        }
                    }
                    else
                    {
                        $block->setAttribute( 'parent_id', 0 );
                        $block->setAttribute( 'placeholder', '' );
                    }
                }
                else
                {
                    $block->setAttribute( 'parent_id', 0 );
                    $block->setAttribute( 'placeholder', '' );
                }

                $block->setAttribute( 'modified', time() );
                $block->store();

                self::placeBlockAt( $block, $requestedPosition === null ? PHP_INT_MAX : $requestedPosition );

                $movedGroups = $originZoneId !== (int)$block->attribute( 'zone_id' )
                    || $originParentId !== (int)$block->attribute( 'parent_id' )
                    || self::normalizedPlaceholder( $originPlaceholder ) !== self::normalizedPlaceholder( $block->attribute( 'placeholder' ) );
                if ( $movedGroups )
                    self::compactSiblings( $originZoneId, $originParentId, $originPlaceholder, $blockStatus );

                return self::response( self::blockToArray( $block ) );
            }

            if ( in_array( $method, array( 'PUT', 'PATCH', 'POST' ) ) )
            {
                $data = self::requestData();
                if ( isset( $data['name'] ) )
                    $block->setAttribute( 'name', trim( $data['name'] ) );
                if ( isset( $data['view_type'] ) )
                    $block->setAttribute( 'view_type', trim( $data['view_type'] ) );
                if ( isset( $data['item_view_type'] ) )
                    $block->setAttribute( 'item_view_type', trim( $data['item_view_type'] ) );
                $requestedPosition = isset( $data['position'] ) ? (int)$data['position'] : null;
                if ( $requestedPosition !== null )
                    $block->setAttribute( 'position', $requestedPosition );
                $block->setAttribute( 'modified', time() );
                $block->store();

                if ( $requestedPosition !== null )
                    self::placeBlockAt( $block, $requestedPosition );

                self::saveBlockParameters( $block, isset( $data['parameters'] ) && is_array( $data['parameters'] ) ? $data['parameters'] : array() );
            }

            return self::response( self::blockToArray( $block ) );
        }

        return self::response( array( 'error' => 'Unknown block request.' ), 404 );
    }

    protected static function handleBlockCollections( $parts, $blocksIndex )
    {
        $blockId = isset( $parts[$blocksIndex + 1] ) ? (int)$parts[$blocksIndex + 1] : 0;
        $collectionIdentifier = isset( $parts[$blocksIndex + 3] ) ? $parts[$blocksIndex + 3] : '';
        $sub = isset( $parts[$blocksIndex + 4] ) ? $parts[$blocksIndex + 4] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        $block = expLayoutsBlock::fetch( $blockId );
        if ( !$block )
            return self::response( array( 'error' => 'Block not found.' ), 404 );

        $collection = self::getCollectionByIdentifier( $blockId, $collectionIdentifier );
        if ( !$collection )
            return self::response( array( 'error' => 'Collection not found.' ), 404 );

        $collectionId = (int)$collection->attribute( 'id' );

        if ( $sub === 'result' && $method === 'GET' )
        {
            return self::response( self::collectionResult( $collection, $block ) );
        }

        if ( $sub === 'change_type' && ( $method === 'POST' || $method === 'PATCH' ) )
        {
            $data = self::requestData();
            $collectionType = '';
            if ( isset( $data['collection_type'] ) && trim( $data['collection_type'] ) !== '' )
                $collectionType = trim( $data['collection_type'] );
            elseif ( isset( $data['new_type'] ) && trim( $data['new_type'] ) !== '' )
                $collectionType = trim( $data['new_type'] );
            elseif ( isset( $data['block_collection']['new_type'] ) && trim( $data['block_collection']['new_type'] ) !== '' )
                $collectionType = trim( $data['block_collection']['new_type'] );

            if ( $collectionType !== '' )
            {
                $oldType = (string)$collection->attribute( 'collection_type' );

                $queryType = '';
                if ( isset( $data['block_collection']['query_type'] ) && trim( $data['block_collection']['query_type'] ) !== '' )
                    $queryType = trim( $data['block_collection']['query_type'] );
                elseif ( isset( $data['query_type'] ) && trim( $data['query_type'] ) !== '' )
                    $queryType = trim( $data['query_type'] );

                $collection->setAttribute( 'collection_type', $collectionType );
                $collection->store();

                if ( $collectionType === 'dynamic' )
                {
                    if ( $queryType === '' )
                        $queryType = 'exponential_content_search';

                    $query = expLayoutsCollectionQuery::fetchByCollection( $collectionId, true );

                    if ( $oldType !== 'dynamic' || !$query || $query->attribute( 'query_type' ) !== $queryType )
                    {
                        $handler = expLayoutsQueryHandlerFactory::get( $queryType );
                        $defaultParameters = self::defaultQueryParameters( $handler );

                        if ( $query && $query->attribute( 'query_type' ) === $queryType )
                        {
                            $existing = @json_decode( (string)$query->attribute( 'parameters' ), true );
                            if ( is_array( $existing ) )
                                $defaultParameters = array_merge( $defaultParameters, $existing );
                        }

                        expLayoutsCollectionQuery::set( $collectionId, $queryType, json_encode( $defaultParameters ) );
                    }
                }
                elseif ( $oldType === 'dynamic' )
                {
                    expLayoutsCollectionQuery::removeByCollection( $collectionId );
                }
            }
            return self::response( self::collectionResult( $collection, $block ) );
        }

        if ( $sub === 'query' && ( $method === 'POST' || $method === 'PATCH' ) )
        {
            $data = self::requestData();
            if ( (string)$collection->attribute( 'collection_type' ) !== 'dynamic' )
            {
                $collection->setAttribute( 'collection_type', 'dynamic' );
            }

            $queryType = 'exponential_content_search';
            if ( isset( $data['query_type'] ) && trim( $data['query_type'] ) !== '' )
                $queryType = trim( $data['query_type'] );

            $parameters = isset( $data['parameters'] ) && is_array( $data['parameters'] ) ? $data['parameters'] : array();
            if ( isset( $data['query_edit']['parameters'] ) && is_array( $data['query_edit']['parameters'] ) )
                $parameters = $data['query_edit']['parameters'];

            $offset = isset( $data['offset'] ) ? (int)$data['offset'] : 0;
            if ( isset( $data['query_edit']['offset'] ) )
                $offset = (int)$data['query_edit']['offset'];
            $limit = isset( $data['limit'] ) ? (int)$data['limit'] : 0;
            if ( isset( $data['query_edit']['limit'] ) )
                $limit = (int)$data['query_edit']['limit'];

            $collection->setAttribute( 'offset_value', $offset );
            $collection->setAttribute( 'limit_value', $limit );
            $collection->store();

            $handler = expLayoutsQueryHandlerFactory::get( $queryType );
            $parameters = self::normalizeQueryParameters( $handler, $parameters );
            expLayoutsCollectionQuery::set( $collectionId, $queryType, json_encode( $parameters ) );

            return self::response( self::blockToArray( $block ) );
        }

        if ( $sub === 'items' )
        {
            $itemId = isset( $parts[$blocksIndex + 5] ) ? (int)$parts[$blocksIndex + 5] : 0;
            $action = isset( $parts[$blocksIndex + 6] ) ? $parts[$blocksIndex + 6] : '';
            return self::handleBlockCollectionItems( $collection, $block, $itemId, $action );
        }

        return self::response( array( 'error' => 'Unknown collection request.' ), 404 );
    }

    protected static function handleBlockCollectionItems( $collection, $block, $itemId, $action )
    {
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
        $collectionId = (int)$collection->attribute( 'id' );

        if ( $itemId > 0 )
        {
            $def = expLayoutsCollectionItem::definition();
            $item = eZPersistentObject::fetchObject( $def, null, array( 'id' => $itemId ) );
            if ( !$item instanceof expLayoutsCollectionItem )
                return self::response( array( 'error' => 'Collection item not found.' ), 404 );

            if ( $item->attribute( 'collection_id' ) != $collectionId )
                return self::response( array( 'error' => 'Item does not belong to this collection.' ), 404 );

            if ( $method === 'GET' )
                return self::response( self::resolveCollectionItem( $item, $collection ) );

            if ( $method === 'PUT' )
            {
                $data = self::requestData();
                if ( isset( $data['position'] ) )
                    $item->setAttribute( 'position', (int)$data['position'] );
                if ( isset( $data['value'] ) )
                    $item->setAttribute( 'value_id', (int)$data['value'] );
                if ( isset( $data['value_type'] ) )
                    $item->setAttribute( 'value_type', trim( $data['value_type'] ) );
                if ( isset( $data['item_type'] ) )
                    $item->setAttribute( 'item_type', trim( $data['item_type'] ) );
                $item->store();
                return self::response( self::resolveCollectionItem( $item, $collection ) );
            }

            if ( $method === 'DELETE' )
            {
                $item->remove();
                return self::response( self::collectionResult( $collection, $block ) );
            }

            return self::response( array( 'error' => 'Unsupported method.' ), 405 );
        }

        if ( $method === 'GET' )
        {
            return self::response( self::collectionResult( $collection, $block ) );
        }

        if ( $method === 'POST' )
        {
            $data = self::requestData();
            $created = array();

            if ( isset( $data['items'] ) && is_array( $data['items'] ) )
            {
                foreach ( $data['items'] as $itemData )
                {
                    $valueId = isset( $itemData['value'] ) ? (int)$itemData['value'] : 0;
                    $valueType = isset( $itemData['value_type'] ) ? $itemData['value_type'] : 'ez_content';
                    $itemType = isset( $itemData['item_type'] ) ? $itemData['item_type'] : 'manual';
                    $position = isset( $itemData['position'] ) ? (int)$itemData['position'] : 0;
                    if ( $valueId <= 0 )
                        continue;

                    $newItem = expLayoutsCollectionItem::create( $collectionId, $valueId, $valueType, $itemType );
                    $newItem->setAttribute( 'position', $position );
                    $newItem->store();
                    $created[] = self::resolveCollectionItem( $newItem, $collection );
                }
            }

            return self::response( self::collectionResult( $collection, $block ) );
        }

        if ( $method === 'DELETE' && $action === 'remove_all' )
        {
            $db = eZDB::instance();
            $db->query( 'DELETE FROM explayouts_collection_item WHERE collection_id = ' . $collectionId );
            return self::response( self::collectionResult( $collection, $block ) );
        }

        return self::response( array( 'error' => 'Unsupported collection items request.' ), 405 );
    }

    public static function renderBlockHtml( $block, $placeholders = array() )
    {
        $name = (string)$block->attribute( 'name' );
        $prepared = expLayoutsRenderer::prepareBlock( $block );
        $values = is_array( $prepared ) && isset( $prepared['values'] ) ? $prepared['values'] : array();
        $definition = (string)$block->attribute( 'definition_identifier' );

        if ( $name === '' )
        {
            // Prefer the definition's configured Name. Falling back to the
            // identifier surfaced internals in the editor - a component block
            // was labelled "Ibexa Component Hero" off ibexa_component_hero.
            $blockInfo = expLayoutsBlockHandlerFactory::getBlockInfo( $definition );
            if ( is_array( $blockInfo ) && isset( $blockInfo['name'] ) && (string)$blockInfo['name'] !== ''
                 && (string)$blockInfo['name'] !== $definition )
            {
                $name = (string)$blockInfo['name'];
            }
            elseif ( $definition === 'tpl_block' )
            {
                $name = 'Template block';
            }
            else
            {
                $name = ucwords( str_replace( '_', ' ', $definition ) );
            }
        }

        if ( is_array( $placeholders ) && !empty( $placeholders ) )
        {
            $viewType = (string)$block->attribute( 'view_type' );
            $content = '<div class="block-content container-content" style="display:flex;gap:8px;">';

            foreach ( $placeholders as $placeholder )
            {
                $identifier = htmlspecialchars( (string)$placeholder['identifier'] );
                $style = '';
                if ( $definition === 'two_columns' )
                {
                    if ( $viewType === 'two_columns_66_33' )
                        $style = $identifier === 'left' ? 'flex:2' : 'flex:1';
                    elseif ( $viewType === 'two_columns_33_66' )
                        $style = $identifier === 'left' ? 'flex:1' : 'flex:2';
                    else
                        $style = 'flex:1';
                }
                else
                {
                    $style = 'flex:1';
                }

                $content .= '<div class="column-placeholder" data-placeholder="' . $identifier . '" style="' . $style . ';"></div>';
            }

            $content .= '</div>';

            return
                '<div class="block-header">' .
                    '<div class="handle" title="Move block"><i class="material-icons">drag_handle</i></div>' .
                    '<div class="template_name">' . htmlspecialchars( $viewType ) . '</div>' .
                    '<div class="name">' . htmlspecialchars( $name ) . '</div>' .
                    '<div class="dropdown">' .
                        '<button class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
                            '<i class="material-icons">more_horiz</i>' .
                        '</button>' .
                        '<ul class="dropdown-menu dropdown-menu-right">' .
                            '<li class="js-modal-mode"><a title="Edit in modal">Edit in modal</a></li>' .
                            '<li class="divider"></li>' .
                            '<li class="js-revert"><a title="Revert block">Revert block</a></li>' .
                            '<li class="js-copy"><a title="Duplicate block">Duplicate block</a></li>' .
                            '<li class="js-destroy"><a title="Delete block">Delete block</a></li>' .
                        '</ul>' .
                    '</div>' .
                '</div>' . $content;
        }

        $content = '';
        $isInlineDefinition = in_array( $definition, array( 'title', 'text', 'rich_text' ) );
        if ( $definition === 'title' )
        {
            $level = isset( $values['level'] ) ? max( 1, min( 6, (int)$values['level'] ) ) : 2;
            $titleContent = !empty( $values['title'] ) ? (string)$values['title'] : '';
            $titleHint = !empty( $values['title'] ) ? '' : ' data-hint="Title"';
            $content .= '<h' . $level . ' data-inline-child data-attr="title"' . $titleHint . '>' . $titleContent . '</h' . $level . '>';
        }
        elseif ( $definition === 'rich_text' )
        {
            $richContent = isset( $values['content'] ) ? (string)$values['content'] : '';
            if ( $richContent === '' )
                $richContent = '<p></p>';
            $content .= '<div class="rich-text-editor" data-attr="content" data-ck-editor="1">' . $richContent . '</div>';
        }
        elseif ( $definition === 'tpl_block' )
        {
            $blockName = isset( $values['block_name'] ) ? (string)$values['block_name'] : '';
            if ( $blockName === '' )
                $blockName = '..........................';
            $content .= '<div class="tpl-block-info" data-attr="block_name">' .
                '<span class="tpl-block-icon"><i class="material-icons">code</i></span>' .
                '<span class="tpl-block-label">Template block:</span> ' .
                '<strong>' . htmlspecialchars( $blockName ) . '</strong>' .
            '</div>';
        }
        elseif ( $definition === 'text' )
        {
            $textContent = isset( $values['content'] ) ? (string)$values['content'] : '';
            $textHint = $textContent === '' ? ' data-hint="No content"' : '';
            $content .= '<span data-inline-child data-attr="content"' . $textHint . '>' . ( $textContent === '' ? '' : nl2br( htmlspecialchars( $textContent ) ) ) . '</span>';
        }
        elseif ( strpos( $definition, 'ibexa_component_' ) === 0 || $definition === 'exp_component' )
        {
            // Mirrors the reference app/block/ibexa_component.html.twig: the
            // referenced component rendered as a single list item, or a notice
            // when nothing is picked.
            $contentId = isset( $values['content'] ) ? (int)$values['content'] : 0;
            $componentNode = self::componentPreviewNode( $contentId );

            if ( $componentNode instanceof eZContentObjectTreeNode )
            {
                $content .= '<div class="list-row"><div class="list-item">' .
                    self::renderItemPreview( $componentNode, (string)$componentNode->attribute( 'name' ) ) .
                    '</div></div>';
            }
            else
            {
                $notice = $contentId > 0
                    ? 'Component ' . $contentId . ' could not be resolved'
                    : 'No component selected';
                $content .= '<p class="block-notice">' . htmlspecialchars( $notice ) . '</p>';
            }
        }
        elseif ( isset( $values['items'] ) && is_array( $values['items'] ) && !empty( $values['items'] ) )
        {
            // Mirror the reference editor preview
            // (@nglayouts_admin app/parts/collection.html.twig): a grid view
            // lays its items out in `number_of_columns` across, so changing
            // that setting is visible in the editor at a glance. Anything with
            // one column falls back to the stacked list markup.
            $parameters = is_array( $prepared ) && isset( $prepared['parameters'] ) ? $prepared['parameters'] : array();
            $columns = isset( $parameters['number_of_columns'] ) ? (int)$parameters['number_of_columns'] : 0;
            if ( $columns <= 0 )
                $columns = 1;
            if ( $columns > 12 )
                $columns = 12;

            // The reference registers only two list previews in the editor
            // (layouts-standard block_view.yaml, app context): list\grid gets
            // the column grid, list\list gets the stacked list. Every other
            // view type - grid_featured included, which has no editor template
            // there - falls back to the stacked list.
            $blockViewType = (string)$block->attribute( 'view_type' );
            if ( $blockViewType !== 'grid' )
                $columns = 1;

            $isGrid = $columns > 1;
            $rowClass = $isGrid ? 'grid-row' : 'list-row';
            $itemClass = $isGrid ? 'grid-item cols-' . $columns : 'list-item';

            $content .= '<div class="' . $rowClass . '">';
            foreach ( $values['items'] as $item )
            {
                $title = '';
                $itemNode = null;
                if ( is_object( $item ) && method_exists( $item, 'attribute' ) )
                {
                    $title = (string)$item->attribute( 'name' );
                    if ( $title === '' && method_exists( $item, 'Name' ) )
                        $title = (string)$item->Name;
                    if ( $item instanceof eZContentObjectTreeNode )
                        $itemNode = $item;
                }
                elseif ( is_array( $item ) )
                {
                    if ( isset( $item['name'] ) )
                        $title = (string)$item['name'];
                    if ( isset( $item['node_id'] ) && (int)$item['node_id'] > 0 )
                        $itemNode = eZContentObjectTreeNode::fetch( (int)$item['node_id'] );
                }

                if ( $title === '' && $itemNode instanceof eZContentObjectTreeNode )
                    $title = (string)$itemNode->attribute( 'name' );
                if ( $title === '' )
                    continue;

                $content .= '<div class="' . $itemClass . '">' .
                    self::renderItemPreview( $itemNode, $title ) .
                    '</div>';
            }
            $content .= '</div>';
        }
        elseif ( !empty( $values ) )
        {
            foreach ( $values as $key => $value )
            {
                if ( is_object( $value ) )
                    continue;
                if ( is_array( $value ) )
                    $value = json_encode( $value );
                $value = (string)$value;
                if ( $value === '' ) continue;
                $content .= '<p class="block-value"><strong>' . htmlspecialchars( (string)$key ) . ':</strong> ' . htmlspecialchars( $value ) . '</p>';
            }
        }

        if ( $content === '' || ( trim( strip_tags( $content ) ) === '' && !$isInlineDefinition ) )
        {
            $content = '<p class="block-empty" style="color:#888; font-style:italic;">No content</p>';
        }

        $viewType = (string)$block->attribute( 'view_type' );
        $viewTypeName = htmlspecialchars( $viewType === 'tpl_block' ? 'TPL_BLOCK' : $viewType );

        $header =
            '<div class="block-header">' .
                '<div class="handle" title="Move block"><i class="material-icons">drag_handle</i></div>' .
                '<div class="template_name">' . $viewTypeName . '</div>' .
                '<div class="name">' . htmlspecialchars( $name ) . '</div>' .
                '<div class="dropdown">' .
                    '<button class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
                        '<i class="material-icons">more_horiz</i>' .
                    '</button>' .
                    '<ul class="dropdown-menu dropdown-menu-right">' .
                        '<li class="js-modal-mode"><a title="Edit in modal">Edit in modal</a></li>' .
                        '<li class="divider"></li>' .
                        '<li class="js-revert"><a title="Revert block">Revert block</a></li>' .
                        '<li class="js-copy"><a title="Duplicate block">Duplicate block</a></li>' .
                        '<li class="js-destroy"><a title="Delete block">Delete block</a></li>' .
                    '</ul>' .
                '</div>' .
            '</div>';

        return $header . '<div class="block-content">' . $content . '</div>';
    }

    /**
     * Main node of the component a content-backed component block references.
     *
     * The stored `content` parameter is a Nexus content id; the imported
     * objects carry remote_id 'media-o-<nexus_id + 776>'. This mirrors the
     * resolution order of the theme's component_content operator so the editor
     * preview and the rendered page agree on which object a block points at.
     */
    protected static function componentPreviewNode( $contentId )
    {
        $contentId = (int)$contentId;
        if ( $contentId <= 0 )
            return null;

        $object = eZContentObject::fetchByRemoteID( 'media-o-' . ( $contentId + 776 ) );
        if ( !$object )
            $object = eZContentObject::fetchByRemoteID( 'media-o-' . $contentId );
        if ( !$object )
            $object = eZContentObject::fetch( $contentId + 776 );
        if ( !$object )
            $object = eZContentObject::fetch( $contentId );

        if ( !$object instanceof eZContentObject )
            return null;

        $node = $object->attribute( 'main_node' );
        return $node instanceof eZContentObjectTreeNode ? $node : null;
    }

    /**
     * Thumbnail URL for a collection item in the editor preview.
     *
     * Mirrors the reference app/item/nglayouts_app_preview.html.twig, which
     * renders the item's image field through a small dedicated alias. Prefers
     * teaser_image over image, matching content/parts/item_image.tpl, and
     * walks the aliases from smallest usable upwards.
     */
    protected static function itemPreviewImageUrl( $node )
    {
        if ( !$node instanceof eZContentObjectTreeNode )
            return '';

        $dataMap = $node->attribute( 'data_map' );
        if ( !is_array( $dataMap ) )
            return '';

        foreach ( array( 'teaser_image', 'image', 'thumbnail' ) as $identifier )
        {
            if ( !isset( $dataMap[$identifier] ) )
                continue;

            $attribute = $dataMap[$identifier];
            if ( !$attribute instanceof eZContentObjectAttribute )
                continue;
            if ( (string)$attribute->attribute( 'data_type_string' ) !== 'ezimage' )
                continue;
            if ( !$attribute->attribute( 'has_content' ) )
                continue;

            $handler = $attribute->content();
            if ( !$handler instanceof eZImageAliasHandler )
                continue;

            $url = self::imageAliasUrl( $handler, array( 'small', 'medium', 'i320', 'large', 'original' ) );
            if ( $url !== '' )
                return $url;
        }

        return '';
    }

    /**
     * First of the given aliases that actually exists on disk, as a
     * root-relative URL.
     *
     * imageAlias() generates a missing variation, and the stored alias list can
     * name files that were never written, so each candidate is checked through
     * the cluster file handler - the same treatment the theme's image operator
     * applies. Alias URLs are stored without a leading slash, which would
     * otherwise resolve against /explayouts_ui_api/app in the editor.
     */
    protected static function imageAliasUrl( $handler, $aliases )
    {
        $clusterFileHandler = eZClusterFileHandler::instance();
        $aliasList = $handler->aliasList();
        if ( !is_array( $aliasList ) )
            $aliasList = array();

        $url = '';
        foreach ( $aliases as $alias )
        {
            if ( isset( $aliasList[$alias]['url'] ) && $aliasList[$alias]['url'] !== ''
                 && $clusterFileHandler->fileExists( $aliasList[$alias]['url'] ) )
            {
                $url = (string)$aliasList[$alias]['url'];
                break;
            }

            $generated = $handler->imageAlias( $alias );
            if ( is_array( $generated ) && !empty( $generated['url'] )
                 && $clusterFileHandler->fileExists( $generated['url'] ) )
            {
                $url = (string)$generated['url'];
                break;
            }
        }

        if ( $url !== '' && strpos( $url, '/' ) !== 0 && strpos( $url, 'http' ) !== 0 )
            $url = '/' . $url;

        return $url;
    }

    /**
     * One collection item as the reference editor renders it: thumbnail, linked
     * name, then the value type and content type
     * (app/item/nglayouts_app_preview.html.twig).
     */
    protected static function renderItemPreview( $node, $title )
    {
        $html = '';

        $imageUrl = self::itemPreviewImageUrl( $node );
        if ( $imageUrl !== '' )
        {
            $html .= '<div class="image"><img src="' . htmlspecialchars( $imageUrl ) .
                '" alt="' . htmlspecialchars( $title ) . '" loading="lazy" /></div>';
        }

        $url = '';
        $typeName = '';
        if ( $node instanceof eZContentObjectTreeNode )
        {
            $url = '/' . ltrim( (string)$node->attribute( 'url_alias' ), '/' );
            eZURI::transformURI( $url );

            $object = $node->attribute( 'object' );
            if ( $object instanceof eZContentObject )
            {
                $contentClass = $object->attribute( 'content_class' );
                if ( $contentClass )
                    $typeName = (string)$contentClass->attribute( 'name' );
            }
        }

        $html .= '<div class="name"><p>';
        if ( $url !== '' )
        {
            $html .= '<a href="' . htmlspecialchars( $url ) .
                '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars( $title ) . '</a>';
        }
        else
        {
            $html .= htmlspecialchars( $title );
        }
        $html .= '</p></div>';

        if ( $typeName !== '' )
        {
            $html .= '<div class="value-type"><p>Location (' . htmlspecialchars( $typeName ) . ')</p></div>';
        }

        return $html;
    }

    protected static function findZoneByIdentifier( $layoutId, $zoneIdentifier )
    {
        foreach ( expLayoutsZone::fetchByLayout( (int)$layoutId, null ) as $zone )
        {
            if ( (string)$zone->attribute( 'identifier' ) === $zoneIdentifier )
                return $zone;
        }
        return false;
    }

    protected static function resolveLinkedZone( $zone )
    {
        return expLayoutsZone::resolveSource( $zone );
    }

    /**
     * The id of the published row of a layout, given any of its rows.
     *
     * The editor looks a linked layout up in the shared-layout collection,
     * which only ever holds published rows, so a link reported against a
     * draft id would come back as an unknown layout.
     */
    protected static function publishedLayoutId( $layoutId )
    {
        $layout = expLayoutsLayout::fetch( (int)$layoutId );
        if ( !$layout )
            return (int)$layoutId;

        if ( (int)$layout->attribute( 'status' ) === 2 )
            return (int)$layout->attribute( 'id' );

        $published = expLayoutsLayout::fetchByIdentifier( (string)$layout->attribute( 'identifier' ), 2 );
        return $published ? (int)$published->attribute( 'id' ) : (int)$layoutId;
    }

    /**
     * The blocks the zone owns.
     *
     * A linked zone owns none: its blocks belong to the shared layout and the
     * editor loads them separately, through the zone-blocks endpoint, so that
     * it can draw them locked rather than as this layout's own.
     */
    /**
     * True for a block that is only ever on screen because some layout links
     * to the shared layout owning it.
     *
     * Such a block reaches the editor through the zone-blocks endpoint and is
     * drawn locked, but the block routes address blocks by id alone, so a
     * stale page or a hand-made request could still aim an edit at it. A
     * published row of a shared layout is never the right target: editing a
     * shared layout goes through that layout's own draft, whose blocks are
     * separate rows at status 1.
     */
    protected static function blockIsInherited( $block )
    {
        if ( (int)$block->attribute( 'status' ) !== 2 )
            return false;

        $layout = expLayoutsLayout::fetch( (int)$block->attribute( 'layout_id' ) );
        return $layout instanceof expLayoutsLayout && $layout->isShared();
    }

    protected static function fetchOwnTopLevelBlocksForZone( $zone )
    {
        if ( $zone->isLinked() )
            return array();

        return self::fetchTopLevelBlocksForZone( $zone );
    }

    protected static function fetchTopLevelBlocksForZone( $zone )
    {
        $sourceZone = self::resolveLinkedZone( $zone );
        $blocks = expLayoutsBlock::fetchByZone( (int)$sourceZone->attribute( 'id' ), null );

        $topLevel = array();
        foreach ( $blocks as $block )
        {
            if ( (int)$block->attribute( 'parent_id' ) !== 0 )
                continue;

            $definition = (string)$block->attribute( 'definition_identifier' );
            if ( $definition === '' )
                continue;

            $topLevel[] = $block;
        }

        usort( $topLevel, function( $a, $b ) {
            $posA = (int)$a->attribute( 'position' );
            $posB = (int)$b->attribute( 'position' );
            if ( $posA !== $posB )
                return $posA - $posB;
            return (int)$a->attribute( 'id' ) - (int)$b->attribute( 'id' );
        } );

        return $topLevel;
    }

    /**
     * An empty placeholder and "main" render as one list (see blockToArray),
     * so they share a single position sequence.
     */
    protected static function normalizedPlaceholder( $placeholder )
    {
        $placeholder = (string)$placeholder;
        return $placeholder === '' ? 'main' : $placeholder;
    }

    /**
     * The blocks that share a position sequence with the given coordinates:
     * the top level of a zone when there is no parent, otherwise one
     * placeholder of the parent container. Sorted by position, then id.
     *
     * Blocks with an empty definition_identifier are the imported zone root
     * artifacts; they render nothing and are left out of the sequence.
     */
    protected static function siblingBlocks( $zoneId, $parentId, $placeholder, $status )
    {
        $parentId = (int)$parentId;
        $bucket = self::normalizedPlaceholder( $placeholder );

        $candidates = $parentId > 0
            ? expLayoutsBlock::fetchChildren( $parentId, $status )
            : expLayoutsBlock::fetchByZone( (int)$zoneId, $status );

        $siblings = array();
        foreach ( $candidates as $candidate )
        {
            if ( (int)$candidate->attribute( 'parent_id' ) !== $parentId )
                continue;
            if ( (string)$candidate->attribute( 'definition_identifier' ) === '' )
                continue;
            // Top-level blocks are rendered as one list per zone regardless of
            // their placeholder (see explayouts/zone.tpl), so only nested
            // blocks are split by placeholder.
            if ( $parentId > 0 && self::normalizedPlaceholder( $candidate->attribute( 'placeholder' ) ) !== $bucket )
                continue;

            $siblings[] = $candidate;
        }

        usort( $siblings, function( $a, $b ) {
            $posA = (int)$a->attribute( 'position' );
            $posB = (int)$b->attribute( 'position' );
            if ( $posA !== $posB )
                return $posA - $posB;
            return (int)$a->attribute( 'id' ) - (int)$b->attribute( 'id' );
        } );

        return $siblings;
    }

    /**
     * Renumber a sibling sequence to 0..n, writing only the rows that move.
     * Returns the ordered sibling list.
     */
    protected static function renumberSiblings( $siblings )
    {
        $position = 0;
        foreach ( $siblings as $sibling )
        {
            if ( (int)$sibling->attribute( 'position' ) !== $position )
            {
                $sibling->setAttribute( 'position', $position );
                $sibling->store();
            }
            $position++;
        }

        return $siblings;
    }

    /**
     * Move an already-stored block to $targetPosition inside its sibling
     * sequence and renumber the whole sequence to 0..n. Without this the API
     * left every block it created at position 0, so ordering silently fell
     * back to insertion order (block id).
     */
    protected static function placeBlockAt( $block, $targetPosition )
    {
        $blockId = (int)$block->attribute( 'id' );
        $siblings = self::siblingBlocks(
            (int)$block->attribute( 'zone_id' ),
            (int)$block->attribute( 'parent_id' ),
            $block->attribute( 'placeholder' ),
            (int)$block->attribute( 'status' )
        );

        // Work from the rows as they are stored. The caller's instance may
        // carry a stale position (it is not updated when an earlier renumber
        // rewrote its row), which would make the renumber below skip it.
        $ordered = array();
        $target = null;
        foreach ( $siblings as $sibling )
        {
            if ( (int)$sibling->attribute( 'id' ) === $blockId )
            {
                $target = $sibling;
                continue;
            }
            $ordered[] = $sibling;
        }
        if ( $target === null )
            $target = $block;

        $targetPosition = (int)$targetPosition;
        if ( $targetPosition < 0 )
            $targetPosition = 0;
        if ( $targetPosition > count( $ordered ) )
            $targetPosition = count( $ordered );

        array_splice( $ordered, $targetPosition, 0, array( $target ) );
        self::renumberSiblings( $ordered );

        // Keep the caller's instance in step so the API response reports the
        // position that was actually written.
        $block->setAttribute( 'position', (int)$target->attribute( 'position' ) );
    }

    /**
     * Close the gap left by a removed or relocated block.
     */
    protected static function compactSiblings( $zoneId, $parentId, $placeholder, $status )
    {
        self::renumberSiblings( self::siblingBlocks( $zoneId, $parentId, $placeholder, $status ) );
    }

    protected static function deleteBlock( $block )
    {
        $blockId = (int)$block->attribute( 'id' );
        foreach ( expLayoutsBlockParameter::fetchByBlock( $blockId ) as $param )
            $param->remove();

        $collection = expLayoutsCollection::fetchByBlock( $blockId );
        if ( $collection )
        {
            foreach ( expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) ) as $item )
                $item->remove();
            $collection->remove();
        }

        foreach ( expLayoutsBlock::fetchChildren( $blockId, null ) as $child )
            self::deleteBlock( $child );

        $block->remove();
    }

    protected static function saveBlockParameters( $block, $parameters )
    {
        if ( empty( $parameters ) )
            return;

        $blockId = (int)$block->attribute( 'id' );
        foreach ( $parameters as $name => $value )
        {
            if ( is_array( $value ) )
            {
                if ( isset( $value['_self'] ) )
                    expLayoutsBlockParameter::set( $blockId, trim( $name ), (string)$value['_self'] );

                foreach ( $value as $childName => $childValue )
                {
                    if ( $childName === '_self' )
                        continue;
                    if ( is_array( $childValue ) )
                        $childValue = json_encode( $childValue );
                    expLayoutsBlockParameter::set( $blockId, trim( $childName ), (string)$childValue );
                }
            }
            else
            {
                expLayoutsBlockParameter::set( $blockId, trim( $name ), (string)$value );
            }
        }
    }

    protected static function requestData()
    {
        if ( !empty( $_POST ) )
            return $_POST;

        $input = file_get_contents( 'php://input' );
        $data = @json_decode( $input, true );
        return is_array( $data ) ? $data : array();
    }

    public static function blockToArray( $block )
    {
        if ( !$block )
            return null;

        $blockId = (int)$block->attribute( 'id' );
        $definitionIdentifier = (string)$block->attribute( 'definition_identifier' );
        $blockInfo = expLayoutsBlockHandlerFactory::getBlockInfo( $definitionIdentifier );
        $isContainer = is_array( $blockInfo ) && !empty( $blockInfo['is_container'] );

        $zone = expLayoutsZone::fetch( (int)$block->attribute( 'zone_id' ) );
        $zoneIdentifier = $zone ? (string)$zone->attribute( 'identifier' ) : '';

        $parameters = array(
            'css_id' => '',
            'css_class' => '',
        );
        foreach ( expLayoutsBlockParameter::fetchByBlock( $blockId ) as $param )
        {
            $parameters[(string)$param->attribute( 'name' )] = json_decode( (string)$param->attribute( 'value' ), true ) !== null ? json_decode( (string)$param->attribute( 'value' ), true ) : (string)$param->attribute( 'value' );
        }

        $collections = array();
        $collection = expLayoutsCollection::fetchByBlock( $blockId );
        if ( $collection instanceof expLayoutsCollection )
        {
            $collections[] = self::collectionToArray( $collection, $block );
        }
        elseif ( is_array( $blockInfo ) && !empty( $blockInfo['has_collection'] ) )
        {
            $collection = expLayoutsCollection::create( $blockId, 'manual' );
            $collection->store();
            $collections[] = self::collectionToArray( $collection, $block );
        }

        $placeholders = array();
        if ( $isContainer )
        {
            $children = expLayoutsBlock::fetchChildren( $blockId, null );
            $placeholderNames = is_array( $blockInfo ) && !empty( $blockInfo['placeholders'] ) ? $blockInfo['placeholders'] : array( 'main' );
            $childrenByPlaceholder = array();
            foreach ( $placeholderNames as $placeholderName )
                $childrenByPlaceholder[$placeholderName] = array();

            foreach ( $children as $child )
            {
                $placeholderName = (string)$child->attribute( 'placeholder' );
                if ( $placeholderName === '' )
                    $placeholderName = 'main';
                if ( !isset( $childrenByPlaceholder[$placeholderName] ) )
                    $childrenByPlaceholder[$placeholderName] = array();
                $childrenByPlaceholder[$placeholderName][] = self::blockToArray( $child );
            }

            foreach ( $childrenByPlaceholder as $placeholderName => $placeholderBlocks )
            {
                $placeholders[] = array(
                    'identifier' => $placeholderName,
                    'name' => $placeholderName,
                    'blocks' => $placeholderBlocks,
                );
            }
        }

        return array(
            'id' => $blockId,
            'identifier' => $definitionIdentifier,
            'definition_identifier' => $definitionIdentifier,
            'name' => (string)$block->attribute( 'name' ),
            'view_type' => (string)$block->attribute( 'view_type' ),
            'position' => (int)$block->attribute( 'position' ),
            'locale' => 'eng',
            'zone_identifier' => $zoneIdentifier,
            'layout_id' => (int)$block->attribute( 'layout_id' ),
            'parent_block_id' => (int)$block->attribute( 'parent_id' ),
            'parent_placeholder' => (string)$block->attribute( 'placeholder' ),
            'is_container' => $isContainer,
            'has_published_state' => false,
            'parameters' => $parameters,
            'html' => self::renderBlockHtml( $block, $placeholders ),
            'collections' => $collections,
            'placeholders' => $placeholders,
        );
    }

    protected static function getCollectionByIdentifier( $blockId, $identifier )
    {
        if ( $identifier === '' || $identifier === 'default' )
        {
            $collection = expLayoutsCollection::fetchByBlock( (int)$blockId );
            if ( $collection instanceof expLayoutsCollection )
                return $collection;
        }

        $list = eZPersistentObject::fetchObjectList(
            expLayoutsCollection::definition(),
            null,
            array( 'block_id' => (int)$blockId ),
            null,
            null,
            true
        );
        foreach ( $list as $collection )
        {
            if ( (string)$collection->attribute( 'collection_type' ) === $identifier
                 || (string)$collection->attribute( 'id' ) === $identifier )
                return $collection;
        }
        return false;
    }

    public static function collectionToArray( $collection, $block )
    {
        if ( !$collection instanceof expLayoutsCollection )
            return null;

        $collectionId = (int)$collection->attribute( 'id' );
        $queryType = '';
        $queryParameters = array();
        if ( (string)$collection->attribute( 'collection_type' ) === 'dynamic' )
        {
            $query = expLayoutsCollectionQuery::fetchByCollection( $collectionId, true );
            if ( $query )
            {
                $queryType = (string)$query->attribute( 'query_type' );
                $queryParameters = @json_decode( (string)$query->attribute( 'parameters' ), true );
                if ( !is_array( $queryParameters ) )
                    $queryParameters = array();
            }
        }

        return array(
            'id' => $collectionId,
            'collection_id' => $collectionId,
            'identifier' => 'default',
            'block_id' => (int)$collection->attribute( 'block_id' ),
            'type' => (string)$collection->attribute( 'collection_type' ),
            'collection_type' => (string)$collection->attribute( 'collection_type' ),
            'query_type' => $queryType,
            'query_parameters' => $queryParameters,
            'offset' => (int)$collection->attribute( 'offset_value' ),
            'limit' => (int)$collection->attribute( 'limit_value' ),
            'offset_value' => (int)$collection->attribute( 'offset_value' ),
            'limit_value' => (int)$collection->attribute( 'limit_value' ),
            'status' => (int)$collection->attribute( 'status' ),
            'items' => array(),
            'overflow_items' => array(),
        );
    }

    protected static function resolveCollectionItem( $item, $collection = null )
    {
        if ( !$item instanceof expLayoutsCollectionItem )
            return null;

        $valueId = (int)$item->attribute( 'value_id' );
        $valueType = (string)$item->attribute( 'value_type' );
        $name = 'Unknown ' . $valueType . ' ' . $valueId;
        $cmsVisible = false;
        $cmsUrl = '';
        $urlAlias = '';

        if ( $valueId > 0 && in_array( $valueType, array( 'ez_content', 'ez_location' ) ) )
        {
            $node = null;
            $object = null;

            if ( $valueType === 'ez_location' )
            {
                $node = eZContentObjectTreeNode::fetch( $valueId );
            }
            else
            {
                $object = eZContentObject::fetch( $valueId );
                if ( $object instanceof eZContentObject )
                {
                    $mainNode = $object->attribute( 'main_node' );
                    if ( $mainNode instanceof eZContentObjectTreeNode )
                        $node = $mainNode;
                }
            }

            if ( $node instanceof eZContentObjectTreeNode )
            {
                if ( !$object instanceof eZContentObject )
                    $object = $node->object();
                if ( $object instanceof eZContentObject )
                {
                    $name = (string)$object->attribute( 'name' );
                    $cmsVisible = true;
                    $cmsUrl = '/content/view/full/' . (int)$node->attribute( 'node_id' );
                }
                $urlAlias = (string)$node->urlAlias();
            }
        }

        return array(
            'id' => (int)$item->attribute( 'id' ),
            'collection_id' => (int)$item->attribute( 'collection_id' ),
            'value' => $valueId,
            'value_id' => $valueId,
            'value_type' => $valueType,
            'item_type' => (string)$item->attribute( 'item_type' ),
            'position' => (int)$item->attribute( 'position' ),
            'name' => $name,
            'is_dynamic' => false,
            'visible' => true,
            'cms_visible' => $cmsVisible,
            'can_remove_item' => true,
            'cms_url' => $cmsUrl,
            'url' => $urlAlias,
            'slot_id' => null,
        );
    }

    public static function collectionResult( $collection, $block )
    {
        $collectionId = (int)$collection->attribute( 'id' );
        $offset = (int)$collection->attribute( 'offset_value' );
        $limit = (int)$collection->attribute( 'limit_value' );
        $collectionType = (string)$collection->attribute( 'collection_type' );

        $allItems = array();
        $total = 0;

        if ( $collectionType === 'dynamic' )
        {
            $dynamicResult = expLayoutsDynamicCollection::fetch( $collection );
            if ( is_array( $dynamicResult ) && isset( $dynamicResult['items'] ) )
            {
                $total = isset( $dynamicResult['total'] ) ? (int)$dynamicResult['total'] : count( $dynamicResult['items'] );
                $pos = (int)$offset;
                foreach ( $dynamicResult['items'] as $node )
                {
                    $pos++;
                    $resolved = self::resolveCollectionNode( $node, $collection, true, $pos );
                    if ( $resolved )
                        $allItems[] = $resolved;
                }
            }
        }
        else
        {
            $params = array(
                'collection_id' => $collectionId,
                'offset' => $offset,
                'limit' => $limit,
            );

            if ( $block instanceof expLayoutsBlock )
            {
                foreach ( expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) ) as $param )
                {
                    $name = (string)$param->attribute( 'name' );
                    $value = (string)$param->attribute( 'value' );
                    $decoded = @json_decode( $value, true );
                    $params[$name] = $decoded !== null ? $decoded : $value;
                }
            }

            $queryType = isset( $params['query_type'] ) && trim( $params['query_type'] ) !== '' ? trim( $params['query_type'] ) : 'manual';

            if ( $queryType === 'manual' )
            {
                $rawItems = expLayoutsCollectionItem::fetchByCollection( $collectionId, true );
                $wrappers = array();
                foreach ( $rawItems as $item )
                {
                    $valueId = (int)$item->attribute( 'value_id' );
                    if ( $valueId <= 0 )
                        continue;

                    $node = eZContentObjectTreeNode::fetch( $valueId );
                    if ( $node )
                        $wrappers[] = array( 'node' => $node, 'item_id' => (int)$item->attribute( 'id' ) );
                }

                $total = count( $wrappers );
                if ( $offset > 0 || $limit > 0 )
                    $wrappers = array_slice( $wrappers, $offset, $limit > 0 ? $limit : null );

                $pos = (int)$offset;
                foreach ( $wrappers as $wrapper )
                {
                    $resolved = self::resolveCollectionNode( $wrapper, $collection, false, $pos );
                    if ( $resolved )
                        $allItems[] = $resolved;
                    $pos++;
                }
            }
            else
            {
                $handler = expLayoutsQueryHandlerFactory::get( $queryType );
                if ( !$handler )
                    $handler = new expLayoutsManualQueryHandler();

                $queryResult = $handler->fetch( $params );
                if ( is_array( $queryResult ) && isset( $queryResult['items'] ) )
                {
                    $total = isset( $queryResult['total'] ) ? (int)$queryResult['total'] : count( $queryResult['items'] );
                    $pos = (int)$offset;
                    foreach ( $queryResult['items'] as $node )
                    {
                        $resolved = self::resolveCollectionNode( $node, $collection, true, $pos );
                        if ( $resolved )
                            $allItems[] = $resolved;
                        $pos++;
                    }
                }
            }
        }

        $result = self::collectionToArray( $collection, $block );
        $result['items'] = $allItems;
        $result['overflow_items'] = array();
        $result['total_count'] = $total;
        $result['item_count'] = count( $allItems );

        return $result;
    }

    protected static function resolveCollectionNode( $node, $collection = null, $isDynamic = false, $position = 0 )
    {
        $collectionItemId = 0;

        if ( is_array( $node ) && isset( $node['node'] ) )
        {
            $collectionItemId = isset( $node['item_id'] ) ? (int)$node['item_id'] : 0;
            $node = $node['node'];
        }

        if ( !$node instanceof eZContentObjectTreeNode )
        {
            if ( is_object( $node ) && $node->hasAttribute( 'node_id' ) )
                $node = eZContentObjectTreeNode::fetch( (int)$node->attribute( 'node_id' ) );
            elseif ( is_array( $node ) && isset( $node['node_id'] ) )
                $node = eZContentObjectTreeNode::fetch( (int)$node['node_id'] );
        }

        if ( !$node instanceof eZContentObjectTreeNode )
            return null;

        $object = $node->object();
        $name = ( $object instanceof eZContentObject ) ? (string)$object->attribute( 'name' ) : (string)$node->attribute( 'name' );
        $cmsVisible = $object instanceof eZContentObject;
        $cmsUrl = '/content/view/full/' . (int)$node->attribute( 'node_id' );
        $urlAlias = (string)$node->urlAlias();
        $nodeId = (int)$node->attribute( 'node_id' );

        return array(
            'id' => $collectionItemId > 0 ? $collectionItemId : $nodeId,
            'collection_id' => ( $collection instanceof expLayoutsCollection ) ? (int)$collection->attribute( 'id' ) : 0,
            'value' => $nodeId,
            'value_id' => $nodeId,
            'value_type' => 'ez_location',
            'item_type' => $isDynamic ? 'dynamic' : 'manual',
            'position' => (int)$position,
            'name' => $name,
            'is_dynamic' => $isDynamic,
            'visible' => true,
            'cms_visible' => $cmsVisible,
            'can_remove_item' => !$isDynamic,
            'cms_url' => $cmsUrl,
            'url' => $urlAlias,
            'slot_id' => null,
        );
    }

    protected static function defaultQueryParameters( $handler )
    {
        $parameters = array();
        if ( $handler && method_exists( $handler, 'getParameters' ) )
        {
            foreach ( $handler->getParameters() as $name => $definition )
            {
                $parameters[$name] = isset( $definition['default'] ) ? $definition['default'] : '';
            }
        }
        return $parameters;
    }

    protected static function normalizeQueryParameters( $handler, $parameters )
    {
        $normalized = array();
        if ( $handler && method_exists( $handler, 'getParameters' ) )
        {
            foreach ( $handler->getParameters() as $name => $definition )
            {
                $type = isset( $definition['type'] ) ? $definition['type'] : 'text';
                if ( $type === 'checkbox' )
                {
                    $normalized[$name] = isset( $parameters[$name] ) && ( $parameters[$name] === '1' || $parameters[$name] === 1 || $parameters[$name] === true || $parameters[$name] === 'on' ) ? '1' : '0';
                }
                elseif ( $type === 'multiselect' )
                {
                    $value = isset( $parameters[$name] ) ? $parameters[$name] : array();
                    $normalized[$name] = is_array( $value ) ? array_values( $value ) : ( $value !== '' ? array( $value ) : array() );
                }
                else
                {
                    $normalized[$name] = isset( $parameters[$name] ) ? trim( (string)$parameters[$name] ) : ( isset( $definition['default'] ) ? $definition['default'] : '' );
                }
            }
        }
        else
        {
            $normalized = $parameters;
        }
        return $normalized;
    }

    public static function buildQueryParameterTree( $handler )
    {
        $definitions = ( $handler && method_exists( $handler, 'getParameters' ) ) ? $handler->getParameters() : array();

        $compoundMap = array(
            'use_topic_from_current_content' => array( 'reverse' => true, 'children' => array( 'topic_content_id' ) ),
            'use_current_location' => array( 'reverse' => true, 'children' => array( 'parent_location_id' ) ),
            'filter_by_content_type' => array( 'reverse' => false, 'children' => array( 'content_types', 'content_types_filter' ) ),
            'filter_by_section' => array( 'reverse' => false, 'children' => array( 'sections' ) ),
            'filter_by_object_state' => array( 'reverse' => false, 'children' => array( 'object_states' ) ),
        );

        $basic = array( 'use_topic_from_current_content', 'use_current_location', 'sort_type', 'sort_direction' );
        $advanced = array();

        $tree = array();
        $used = array();

        // Mark compound children as used so they are rendered only inside their parent.
        foreach ( $compoundMap as $parentName => $compoundInfo )
        {
            if ( isset( $definitions[$parentName] ) )
            {
                foreach ( $compoundInfo['children'] as $childName )
                {
                    if ( isset( $definitions[$childName] ) )
                        $used[$childName] = true;
                }
            }
        }

        foreach ( $definitions as $name => $definition )
        {
            if ( isset( $used[$name] ) )
                continue;

            $def = $definition;
            if ( isset( $compoundMap[$name] ) )
            {
                $def['type'] = 'compound_checkbox';
                $def['no_self'] = true;
                $def['reverse'] = $compoundMap[$name]['reverse'];
                $def['children'] = array();
                foreach ( $compoundMap[$name]['children'] as $childName )
                {
                    if ( isset( $definitions[$childName] ) )
                        $def['children'][$childName] = $definitions[$childName];
                }
            }

            $tree[$name] = $def;
            $used[$name] = true;

            if ( in_array( $name, $basic ) || ( isset( $compoundMap[$name] ) && in_array( $name, $basic ) ) )
            {
                // non-advanced
            }
            else
            {
                $advanced[] = $name;
            }
        }

        // Order basic params explicitly; any that are not in the tree are skipped.
        $orderedBasic = array();
        foreach ( $basic as $name )
        {
            if ( isset( $tree[$name] ) )
                $orderedBasic[] = $name;
        }

        return array( 'tree' => $tree, 'basic' => $orderedBasic, 'advanced' => $advanced );
    }

    protected static function handleRules( $parts )
    {
        $id = isset( $parts[0] ) ? (int)$parts[0] : 0;
        $sub = isset( $parts[1] ) ? $parts[1] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
        $service = new expLayoutsCoreRuleService();

        if ( $method === 'GET' )
        {
            if ( $id > 0 )
            {
                $rule = $service->load( $id );
                return self::response( $rule ? self::ruleToArray( $rule ) : array( 'error' => 'Rule not found.' ), $rule ? 200 : 404 );
            }

            $rules = $service->listAll();
            return self::response( array( 'values' => array_map( array( __CLASS__, 'ruleToArray' ), $rules ), 'total' => count( $rules ) ) );
        }

        if ( $method === 'POST' && $id === 0 )
        {
            $data = self::requestData();
            $layoutId = isset( $data['layout_id'] ) ? (int)$data['layout_id'] : 0;
            $priority = isset( $data['priority'] ) ? (int)$data['priority'] : 0;
            $enabled = isset( $data['enabled'] ) ? $data['enabled'] : ( isset( $data['is_enabled'] ) ? $data['is_enabled'] : true );

            $rule = $service->create( $layoutId, $priority, $enabled ? 1 : 0 );
            $ruleId = (int)$rule->attribute( 'id' );
            $service->setTargets( $ruleId, self::normalizeRuleTargets( $data ) );
            $service->setConditions( $ruleId, self::normalizeRuleConditions( $data ) );

            return self::response( self::ruleToArray( $service->load( $ruleId ) ), 201 );
        }

        if ( ( $method === 'PUT' || $method === 'PATCH' ) && $id > 0 )
        {
            $data = self::requestData();
            $updates = array();
            if ( isset( $data['layout_id'] ) )
                $updates['layout_id'] = (int)$data['layout_id'];
            if ( isset( $data['priority'] ) )
                $updates['priority'] = (int)$data['priority'];
            if ( isset( $data['enabled'] ) )
                $updates['enabled'] = $data['enabled'] ? 1 : 0;
            elseif ( isset( $data['is_enabled'] ) )
                $updates['enabled'] = $data['is_enabled'] ? 1 : 0;

            $rule = $service->update( $id, $updates );
            if ( !$rule )
                return self::response( array( 'error' => 'Rule not found.' ), 404 );

            $service->setTargets( $id, self::normalizeRuleTargets( $data ) );
            $service->setConditions( $id, self::normalizeRuleConditions( $data ) );

            return self::response( self::ruleToArray( $service->load( $id ) ), 200 );
        }

        if ( $method === 'POST' && $id > 0 && $sub === 'copy' )
        {
            $copy = $service->copy( $id );
            if ( !$copy )
                return self::response( array( 'error' => 'Rule not found.' ), 404 );
            return self::response( self::ruleToArray( $copy ), 201 );
        }

        if ( $method === 'DELETE' && $id > 0 )
        {
            if ( !$service->delete( $id ) )
                return self::response( array( 'error' => 'Rule not found.' ), 404 );
            return self::response( array( 'deleted' => true ), 204 );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
    }

    protected static function normalizeRuleTargets( $data )
    {
        $out = array();
        $items = isset( $data['targets'] ) && is_array( $data['targets'] ) ? $data['targets'] : array();
        foreach ( $items as $item )
        {
            if ( !is_array( $item ) )
                continue;
            $type = isset( $item['type'] ) ? $item['type'] : ( isset( $item['target_type'] ) ? $item['target_type'] : '' );
            $value = isset( $item['value'] ) ? $item['value'] : ( isset( $item['target_value'] ) ? $item['target_value'] : '' );
            if ( trim( $type ) !== '' )
                $out[] = array( 'type' => trim( $type ), 'value' => (string)$value );
        }
        return $out;
    }

    protected static function normalizeRuleConditions( $data )
    {
        $out = array();
        $items = isset( $data['conditions'] ) && is_array( $data['conditions'] ) ? $data['conditions'] : array();
        foreach ( $items as $item )
        {
            if ( !is_array( $item ) )
                continue;
            $type = isset( $item['type'] ) ? $item['type'] : ( isset( $item['condition_type'] ) ? $item['condition_type'] : '' );
            $value = isset( $item['value'] ) ? $item['value'] : ( isset( $item['condition_value'] ) ? $item['condition_value'] : '' );
            if ( trim( $type ) !== '' )
                $out[] = array( 'type' => trim( $type ), 'value' => (string)$value );
        }
        return $out;
    }

    protected static function handleMappings( $parts )
    {
        $service = new expLayoutsCoreRuleService();
        $rules = $service->listAll();
        $counts = array();
        foreach ( $rules as $rule )
        {
            $layoutId = (int)$rule->attribute( 'layout_id' );
            $counts[$layoutId] = isset( $counts[$layoutId] ) ? $counts[$layoutId] + 1 : 1;
        }

        $values = array();
        foreach ( $counts as $layoutId => $count )
        {
            $values[] = array( 'layout_id' => $layoutId, 'count' => $count );
        }

        return self::response( array( 'values' => $values, 'total' => count( $values ) ) );
    }

    public static function ruleToArray( $rule )
    {
        if ( !$rule )
            return null;

        $targets = array();
        foreach ( $rule->targets() as $target )
        {
            $targets[] = array(
                'id' => (int)$target->attribute( 'id' ),
                'target_type' => (string)$target->attribute( 'target_type' ),
                'target_value' => (string)$target->attribute( 'target_value' ),
            );
        }

        $conditions = array();
        foreach ( $rule->conditions() as $condition )
        {
            $conditions[] = array(
                'id' => (int)$condition->attribute( 'id' ),
                'condition_type' => (string)$condition->attribute( 'condition_type' ),
                'condition_value' => (string)$condition->attribute( 'condition_value' ),
            );
        }

        return array(
            'id' => (int)$rule->attribute( 'id' ),
            'layout_id' => (int)$rule->attribute( 'layout_id' ),
            'priority' => (int)$rule->attribute( 'priority' ),
            'enabled' => (bool)$rule->attribute( 'enabled' ),
            'targets' => $targets,
            'conditions' => $conditions,
        );
    }

    public static function layoutToArray( $layout )
    {
        if ( !$layout )
            return null;

        $layoutId = (int)$layout->attribute( 'id' );
        $status = (int)$layout->attribute( 'status' );
        $layoutType = (string)$layout->attribute( 'layout_type' );
        $hasPublishedState = expLayoutsLayout::fetchByIdentifier( (string)$layout->attribute( 'identifier' ), 2 ) !== false;

        $zoneObjects = expLayoutsZone::fetchByLayout( $layoutId, null );
        $existingZones = array();
        foreach ( $zoneObjects as $zone )
        {
            $existingZones[(string)$zone->attribute( 'identifier' )] = $zone;
        }

        $position = 1;
        foreach ( expLayoutsLayoutType::getZones( $layoutType ) as $zoneIdentifier )
        {
            if ( !isset( $existingZones[$zoneIdentifier] ) )
            {
                $newZone = expLayoutsZone::create( $layoutId, $zoneIdentifier, $status );
                $newZone->setAttribute( 'position', $position );
                $newZone->store();
                $existingZones[$zoneIdentifier] = $newZone;
            }
            $position++;
        }

        $zones = array();
        $zoneHtmlParts = array();
        foreach ( expLayoutsLayoutType::getZones( $layoutType ) as $zoneIdentifier )
        {
            if ( !isset( $existingZones[$zoneIdentifier] ) )
                continue;

            $zone = $existingZones[$zoneIdentifier];
            $blockIds = array();
            foreach ( self::fetchOwnTopLevelBlocksForZone( $zone ) as $block )
            {
                $blockIds[] = (int)$block->attribute( 'id' );
            }

            // A linked zone reports the link and no blocks of its own. The
            // editor keys "inherited" off linked_zone_identifier and pulls the
            // shared layout's blocks itself, through the zone-blocks endpoint.
            $zones[] = array(
                'identifier' => $zoneIdentifier,
                'name' => $zoneIdentifier,
                'block_ids' => $blockIds,
                'linked_layout_id' => $zone->isLinked() ? self::publishedLayoutId( (int)$zone->attribute( 'linked_layout_id' ) ) : null,
                'linked_zone_identifier' => $zone->isLinked() ? (string)$zone->attribute( 'linked_zone_identifier' ) : null,
            );

            $zoneHtmlParts[] = '<div class="zone" data-zone="' . htmlspecialchars( $zoneIdentifier, ENT_QUOTES, 'UTF-8' ) . '"></div>';
        }

        $zonesWithAllowed = array();
        foreach ( $zones as $zone )
        {
            $zone['allowed_block_definitions'] = true;
            $zonesWithAllowed[] = $zone;
        }

        return array(
            'id' => $layoutId,
            'identifier' => (string)$layout->attribute( 'identifier' ),
            'name' => (string)$layout->attribute( 'name' ),
            'type' => $layoutType,
            'layout_type' => $layoutType,
            'description' => '',
            'status' => $status,
            'published' => $status === 2,
            'shared' => (int)$layout->attribute( 'shared' ) === 1,
            'has_published_state' => $hasPublishedState,
            'has_archived_state' => false,
            'created' => (int)$layout->attribute( 'created' ),
            'modified' => (int)$layout->attribute( 'modified' ),
            'zones' => $zonesWithAllowed,
            'html' => implode( "\n", $zoneHtmlParts ),
            'available_locales' => array( 'eng' => 'English' ),
            'main_locale' => 'eng',
            'locale' => 'eng',
        );
    }

    protected static function handleTransfer( $parts )
    {
        $action = isset( $parts[0] ) ? $parts[0] : '';

        if ( $action === 'export' )
        {
            $id = isset( $parts[1] ) ? (int)$parts[1] : 0;
            if ( $id <= 0 )
                return self::response( array( 'error' => 'Missing layout id.' ), 400 );

            $layout = expLayoutsLayout::fetch( $id );
            if ( !$layout instanceof expLayoutsLayout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );

            return self::response( self::exportLayout( $layout ) );
        }

        if ( $action === 'import' )
        {
            $data = self::requestData();
            if ( empty( $data ) )
                return self::response( array( 'error' => 'Missing import payload.' ), 400 );

            $layout = self::importLayout( $data );
            if ( !$layout instanceof expLayoutsLayout )
                return self::response( array( 'error' => 'Import failed.' ), 500 );

            return self::response( self::exportLayout( $layout ) );
        }

        return self::response( array( 'error' => 'Unknown transfer action.' ), 404 );
    }

    protected static function exportLayout( expLayoutsLayout $layout )
    {
        $layoutId = (int)$layout->attribute( 'id' );
        $result = self::objectToArray( $layout );

        $zones = array();
        foreach ( expLayoutsZone::fetchByLayout( $layoutId ) as $zone )
        {
            $zoneData = self::objectToArray( $zone );
            $zoneData['blocks'] = array();
            foreach ( expLayoutsBlock::fetchByZone( $zone->attribute( 'id' ) ) as $block )
            {
                $blockData = self::objectToArray( $block );
                $blockId = (int)$block->attribute( 'id' );
                $blockData['parameters'] = self::listToArrays( expLayoutsBlockParameter::fetchByBlock( $blockId ) );
                $blockData['collection'] = array();
                $collection = expLayoutsCollection::fetchByBlock( $blockId );
                if ( $collection instanceof expLayoutsCollection )
                {
                    $collectionData = self::objectToArray( $collection );
                    $collectionData['items'] = self::listToArrays( expLayoutsCollectionItem::fetchByCollection( $collection->attribute( 'id' ) ) );
                    $blockData['collection'] = $collectionData;
                }
                $zoneData['blocks'][] = $blockData;
            }
            $zones[] = $zoneData;
        }
        $result['zones'] = $zones;

        $result['rules'] = self::rulesForLayout( $layoutId );

        return $result;
    }

    protected static function importLayout( $data )
    {
        if ( !is_array( $data )
            || !isset( $data['identifier'] )
            || !isset( $data['name'] )
            || !isset( $data['layout_type'] ) )
            return false;

        $identifier = $data['identifier'];
        $existing = expLayoutsLayout::fetchByIdentifier( $identifier, 2 );
        if ( $existing instanceof expLayoutsLayout )
            $identifier .= '_import_' . time();

        $layout = expLayoutsLayout::create( $identifier, $data['name'], $data['layout_type'] );
        $layout->store();
        $layoutId = (int)$layout->attribute( 'id' );

        if ( isset( $data['zones'] ) && is_array( $data['zones'] ) )
        {
            foreach ( $data['zones'] as $zoneData )
            {
                $zoneIdentifier = isset( $zoneData['identifier'] ) ? $zoneData['identifier'] : 'zone_' . time();
                $zone = expLayoutsZone::create( $layoutId, $zoneIdentifier );
                if ( isset( $zoneData['status'] ) )
                    $zone->setAttribute( 'status', (int)$zoneData['status'] );
                if ( isset( $zoneData['position'] ) )
                    $zone->setAttribute( 'position', (int)$zoneData['position'] );
                if ( isset( $zoneData['linked_layout_id'] ) )
                {
                    $zone->setAttribute( 'linked_layout_id', (int)$zoneData['linked_layout_id'] );
                    $zone->setAttribute( 'linked_zone_identifier',
                        isset( $zoneData['linked_zone_identifier'] ) ? $zoneData['linked_zone_identifier'] : $zoneIdentifier );
                }
                $zone->store();
                $zoneId = (int)$zone->attribute( 'id' );

                if ( isset( $zoneData['blocks'] ) && is_array( $zoneData['blocks'] ) )
                {
                    foreach ( $zoneData['blocks'] as $blockData )
                    {
                        $definitionIdentifier = isset( $blockData['definition_identifier'] ) ? $blockData['definition_identifier'] : 'text';
                        $name = isset( $blockData['name'] ) ? $blockData['name'] : '';
                        $block = expLayoutsBlock::create( $zoneId, $layoutId, $definitionIdentifier, $name );
                        if ( isset( $blockData['position'] ) )
                            $block->setAttribute( 'position', (int)$blockData['position'] );
                        if ( isset( $blockData['view_type'] ) )
                            $block->setAttribute( 'view_type', $blockData['view_type'] );
                        if ( isset( $blockData['status'] ) )
                            $block->setAttribute( 'status', (int)$blockData['status'] );
                        $block->store();
                        $blockId = (int)$block->attribute( 'id' );

                        if ( isset( $blockData['parameters'] ) && is_array( $blockData['parameters'] ) )
                        {
                            foreach ( $blockData['parameters'] as $param )
                            {
                                if ( isset( $param['name'] ) )
                                    expLayoutsBlockParameter::set(
                                        $blockId,
                                        $param['name'],
                                        isset( $param['value'] ) ? $param['value'] : ''
                                    );
                            }
                        }

                        if ( isset( $blockData['collection'] ) && is_array( $blockData['collection'] ) )
                        {
                            $collectionData = $blockData['collection'];
                            $collectionType = isset( $collectionData['collection_type'] ) ? $collectionData['collection_type'] : 'manual';
                            $collection = expLayoutsCollection::create( $blockId, $collectionType );
                            if ( isset( $collectionData['offset_value'] ) )
                                $collection->setAttribute( 'offset_value', (int)$collectionData['offset_value'] );
                            if ( isset( $collectionData['limit_value'] ) )
                                $collection->setAttribute( 'limit_value', (int)$collectionData['limit_value'] );
                            $collection->store();
                            $collectionId = (int)$collection->attribute( 'id' );

                            if ( isset( $collectionData['items'] ) && is_array( $collectionData['items'] ) )
                            {
                                foreach ( $collectionData['items'] as $itemData )
                                {
                                    $valueId = isset( $itemData['value_id'] ) ? (int)$itemData['value_id'] : 0;
                                    $valueType = isset( $itemData['value_type'] ) ? $itemData['value_type'] : 'ez_content';
                                    $itemType = isset( $itemData['item_type'] ) ? $itemData['item_type'] : 'manual';
                                    if ( $valueId > 0 )
                                    {
                                        $item = expLayoutsCollectionItem::create( $collectionId, $valueId, $valueType, $itemType );
                                        if ( isset( $itemData['position'] ) )
                                            $item->setAttribute( 'position', (int)$itemData['position'] );
                                        $item->store();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ( isset( $data['rules'] ) && is_array( $data['rules'] ) )
        {
            foreach ( $data['rules'] as $ruleData )
            {
                $priority = isset( $ruleData['priority'] ) ? (int)$ruleData['priority'] : null;
                $rule = expLayoutsRule::create( $layoutId, $priority );
                if ( isset( $ruleData['enabled'] ) )
                    $rule->setAttribute( 'enabled', (int)$ruleData['enabled'] ? 1 : 0 );
                $rule->store();
                $ruleId = (int)$rule->attribute( 'id' );

                if ( isset( $ruleData['targets'] ) && is_array( $ruleData['targets'] ) )
                {
                    foreach ( $ruleData['targets'] as $target )
                    {
                        $type = isset( $target['target_type'] ) ? $target['target_type'] : '';
                        $value = isset( $target['target_value'] ) ? $target['target_value'] : '';
                        if ( $type !== '' )
                        {
                            $t = expLayoutsRuleTarget::create( $ruleId, $type, $value );
                            $t->store();
                        }
                    }
                }

                if ( isset( $ruleData['conditions'] ) && is_array( $ruleData['conditions'] ) )
                {
                    foreach ( $ruleData['conditions'] as $condition )
                    {
                        $type = isset( $condition['condition_type'] ) ? $condition['condition_type'] : '';
                        $value = isset( $condition['condition_value'] ) ? $condition['condition_value'] : '';
                        if ( $type !== '' )
                        {
                            $c = expLayoutsRuleCondition::create( $ruleId, $type, $value );
                            $c->store();
                        }
                    }
                }
            }
        }

        return $layout;
    }

    protected static function handleContentBrowser( $parts )
    {
        $parentNodeId = isset( $_GET['parent_node_id'] ) ? (int)$_GET['parent_node_id'] : 2;
        $search = isset( $_GET['search'] ) ? trim( $_GET['search'] ) : '';
        $offset = isset( $_GET['offset'] ) ? (int)$_GET['offset'] : 0;
        $limit = isset( $_GET['limit'] ) ? (int)$_GET['limit'] : 25;

        if ( !class_exists( 'expLayoutsContentBrowserCoreBackend' ) )
            require_once 'extension/explayouts_content_browser_core/classes/explayoutscontentbrowsercorebackend.php';
        if ( !class_exists( 'expLayoutsContentBrowserItem' ) )
            require_once 'extension/explayouts_content_browser/classes/explayoutscontentbrowseritem.php';

        $backend = new expLayoutsContentBrowserCoreBackend( array(), array() );

        if ( $search !== '' )
        {
            $items = $backend->searchItems( $search, $parentNodeId, $offset, $limit );
            $total = $backend->searchItemsCount( $search, $parentNodeId );
        }
        else
        {
            $items = $backend->getSubItems( $parentNodeId, $offset, $limit );
            $total = $backend->getSubItemsCount( $parentNodeId );
        }

        return self::response( array(
            'values' => array_map( function( $item ) { return $item->toArray(); }, $items ),
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'has_next' => $total > $offset + count( $items ),
            'parent_node_id' => $parentNodeId,
        ) );
    }

    protected static function handleCollections( $parts )
    {
        $id = isset( $parts[0] ) ? $parts[0] : '';
        $sub = isset( $parts[1] ) ? $parts[1] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( $id === 'items' )
        {
            $itemId = isset( $parts[1] ) ? (int)$parts[1] : 0;
            $action = isset( $parts[2] ) ? $parts[2] : '';
            return self::handleCollectionItemById( $itemId, $action );
        }

        $id = (int)$id;
        if ( $sub === 'items' && $id > 0 )
            return self::handleCollectionItems( $id, array_slice( $parts, 2 ) );

        if ( $id > 0 )
        {
            $def = expLayoutsCollection::definition();
            $collection = eZPersistentObject::fetchObject( $def, null, array( 'id' => $id ) );
            if ( !$collection instanceof expLayoutsCollection )
                return self::response( array( 'error' => 'Collection not found.' ), 404 );

            if ( $method === 'GET' )
                return self::response( self::objectToArray( $collection ) );
            if ( $method === 'PUT' )
            {
                $data = self::requestData();
                if ( isset( $data['collection_type'] ) )
                    $collection->setAttribute( 'collection_type', $data['collection_type'] );
                if ( isset( $data['offset_value'] ) )
                    $collection->setAttribute( 'offset_value', (int)$data['offset_value'] );
                if ( isset( $data['limit_value'] ) )
                    $collection->setAttribute( 'limit_value', (int)$data['limit_value'] );
                if ( isset( $data['status'] ) )
                    $collection->setAttribute( 'status', (int)$data['status'] );
                $collection->store();
                return self::response( self::objectToArray( $collection ) );
            }
            if ( $method === 'DELETE' )
            {
                $db = eZDB::instance();
                $db->query( 'DELETE FROM explayouts_collection_item WHERE collection_id = ' . $id );
                $db->query( 'DELETE FROM explayouts_collection WHERE id = ' . $id );
                return self::response( array( 'deleted' => $id ) );
            }
            return self::response( array( 'error' => 'Unsupported method.' ), 405 );
        }

        if ( $method === 'GET' )
        {
            $list = eZPersistentObject::fetchObjectList( expLayoutsCollection::definition(), null, array(), array( 'id' => 'desc' ), null, true );
            return self::response( self::listToArrays( $list ) );
        }

        if ( $method === 'POST' )
        {
            $data = self::requestData();
            $blockId = isset( $data['block_id'] ) ? (int)$data['block_id'] : 0;
            if ( $blockId <= 0 )
                return self::response( array( 'error' => 'Missing block_id.' ), 400 );

            $type = isset( $data['collection_type'] ) ? $data['collection_type'] : 'manual';
            $collection = expLayoutsCollection::create( $blockId, $type );
            if ( isset( $data['offset_value'] ) )
                $collection->setAttribute( 'offset_value', (int)$data['offset_value'] );
            if ( isset( $data['limit_value'] ) )
                $collection->setAttribute( 'limit_value', (int)$data['limit_value'] );
            $collection->store();
            return self::response( self::objectToArray( $collection ), 201 );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
    }

    protected static function handleCollectionItemById( $itemId, $action )
    {
        if ( $itemId <= 0 )
            return self::response( array( 'error' => 'Missing collection item id.' ), 400 );

        $def = expLayoutsCollectionItem::definition();
        $item = eZPersistentObject::fetchObject( $def, null, array( 'id' => $itemId ) );
        if ( !$item instanceof expLayoutsCollectionItem )
            return self::response( array( 'error' => 'Collection item not found.' ), 404 );

        $collectionId = (int)$item->attribute( 'collection_id' );
        $collection = eZPersistentObject::fetchObject( expLayoutsCollection::definition(), null, array( 'id' => $collectionId ) );
        if ( !$collection instanceof expLayoutsCollection )
            return self::response( array( 'error' => 'Collection not found.' ), 404 );

        $block = expLayoutsBlock::fetch( (int)$collection->attribute( 'block_id' ) );
        if ( !$block )
            $block = new stdClass();

        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( $method === 'GET' )
            return self::response( self::resolveCollectionItem( $item, $collection ) );

        if ( $method === 'PUT' )
        {
            $data = self::requestData();
            if ( isset( $data['value'] ) )
                $item->setAttribute( 'value_id', (int)$data['value'] );
            if ( isset( $data['value_type'] ) )
                $item->setAttribute( 'value_type', $data['value_type'] );
            if ( isset( $data['item_type'] ) )
                $item->setAttribute( 'item_type', $data['item_type'] );
            if ( isset( $data['position'] ) )
                $item->setAttribute( 'position', (int)$data['position'] );
            $item->store();
            return self::response( self::resolveCollectionItem( $item, $collection ) );
        }

        if ( $method === 'POST' || $method === 'PATCH' )
        {
            if ( $action === 'move' )
            {
                $data = self::requestData();
                if ( isset( $data['position'] ) )
                {
                    $item->setAttribute( 'position', (int)$data['position'] );
                    $item->store();
                }
                return self::response( self::resolveCollectionItem( $item, $collection ) );
            }
        }

        if ( $method === 'DELETE' )
        {
            $item->remove();
            return self::response( self::collectionResult( $collection, $block ) );
        }

        return self::response( array( 'error' => 'Unsupported collection item request.' ), 405 );
    }

    protected static function handleCollectionItems( $collectionId, $parts )
    {
        $itemId = isset( $parts[0] ) ? (int)$parts[0] : 0;
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
        $def = expLayoutsCollectionItem::definition();

        if ( $itemId > 0 )
        {
            $item = eZPersistentObject::fetchObject( $def, null, array( 'id' => $itemId ) );
            if ( !$item instanceof expLayoutsCollectionItem )
                return self::response( array( 'error' => 'Collection item not found.' ), 404 );

            if ( $method === 'GET' )
                return self::response( self::objectToArray( $item ) );
            if ( $method === 'PUT' )
            {
                $data = self::requestData();
                if ( isset( $data['value_type'] ) )
                    $item->setAttribute( 'value_type', $data['value_type'] );
                if ( isset( $data['value_id'] ) )
                    $item->setAttribute( 'value_id', (int)$data['value_id'] );
                if ( isset( $data['item_type'] ) )
                    $item->setAttribute( 'item_type', $data['item_type'] );
                if ( isset( $data['position'] ) )
                    $item->setAttribute( 'position', (int)$data['position'] );
                $item->store();
                return self::response( self::objectToArray( $item ) );
            }
            if ( $method === 'DELETE' )
            {
                $db = eZDB::instance();
                $db->query( 'DELETE FROM explayouts_collection_item WHERE id = ' . $itemId );
                return self::response( array( 'deleted' => $itemId ) );
            }
            return self::response( array( 'error' => 'Unsupported method.' ), 405 );
        }

        if ( $method === 'GET' )
        {
            $list = expLayoutsCollectionItem::fetchByCollection( $collectionId );
            return self::response( self::listToArrays( $list ) );
        }

        if ( $method === 'DELETE' )
        {
            $collection = eZPersistentObject::fetchObject( expLayoutsCollection::definition(), null, array( 'id' => (int)$collectionId ) );
            if ( !$collection instanceof expLayoutsCollection )
                return self::response( array( 'error' => 'Collection not found.' ), 404 );

            $db = eZDB::instance();
            $db->query( 'DELETE FROM explayouts_collection_item WHERE collection_id = ' . (int)$collectionId );

            $block = expLayoutsBlock::fetch( (int)$collection->attribute( 'block_id' ) );
            if ( !$block )
                $block = new stdClass();
            return self::response( self::collectionResult( $collection, $block ) );
        }

        if ( $method === 'POST' )
        {
            $data = self::requestData();
            $valueId = isset( $data['value_id'] ) ? (int)$data['value_id'] : 0;
            $valueType = isset( $data['value_type'] ) ? $data['value_type'] : 'ez_content';
            $itemType = isset( $data['item_type'] ) ? $data['item_type'] : 'manual';
            if ( $valueId <= 0 )
                return self::response( array( 'error' => 'Missing value_id.' ), 400 );
            $item = expLayoutsCollectionItem::create( $collectionId, $valueId, $valueType, $itemType );
            if ( isset( $data['position'] ) )
                $item->setAttribute( 'position', (int)$data['position'] );
            $item->store();
            return self::response( self::objectToArray( $item ), 201 );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
    }

    protected static function handleForms( $parts )
    {
        $identifier = isset( $parts[0] ) ? $parts[0] : '';
        if ( $identifier !== '' )
        {
            $info = expLayoutsBlockHandlerFactory::getBlockInfo( $identifier );
            if ( !is_array( $info ) )
                return self::response( array( 'error' => 'Unknown block identifier.' ), 404 );

            $handler = expLayoutsBlockHandlerFactory::get( $identifier );
            if ( $handler instanceof expLayoutsBlockHandlerInterface )
                $info['parameters'] = $handler->getParameters();
            else
                $info['parameters'] = array();

            return self::response( $info );
        }

        $blocks = array();
        foreach ( expLayoutsBlockHandlerFactory::getAvailableBlocks() as $id )
        {
            $info = expLayoutsBlockHandlerFactory::getBlockInfo( $id );
            if ( is_array( $info ) )
                $blocks[] = $info;
        }
        return self::response( $blocks );
    }

    protected static function handleParameters( $parts )
    {
        $key = isset( $parts[0] ) ? $parts[0] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $ini = eZINI::instance( 'explayouts.ini' );
        $group = 'Parameters';

        if ( $method === 'GET' )
        {
            if ( $key !== '' )
            {
                if ( !$ini->hasVariable( $group, $key ) )
                    return self::response( array( 'error' => 'Parameter not found.' ), 404 );
                return self::response( array( 'name' => $key, 'value' => $ini->variable( $group, $key ) ) );
            }
            $values = $ini->hasGroup( $group ) ? $ini->group( $group ) : array();
            return self::response( $values );
        }

        return self::response( array( 'error' => 'Parameter writes are not supported in this port stage.' ), 501 );
    }

    protected static function handleVersions( $parts )
    {
        $layoutId = isset( $parts[0] ) ? (int)$parts[0] : 0;
        if ( $layoutId <= 0 )
            return self::response( array( 'error' => 'Missing layout id.' ), 400 );

        $layout = expLayoutsLayout::fetch( $layoutId );
        if ( !$layout instanceof expLayoutsLayout )
            return self::response( array( 'error' => 'Layout not found.' ), 404 );

        $identifier = $layout->attribute( 'identifier' );
        $published = expLayoutsLayout::fetchByIdentifier( $identifier, 2 );
        $draft = expLayoutsLayout::fetchByIdentifier( $identifier, 1 );

        $versions = array();
        if ( $published instanceof expLayoutsLayout )
            $versions[] = array( 'status' => 'published', 'id' => (int)$published->attribute( 'id' ), 'created' => (int)$published->attribute( 'created' ), 'modified' => (int)$published->attribute( 'modified' ) );
        if ( $draft instanceof expLayoutsLayout )
            $versions[] = array( 'status' => 'draft', 'id' => (int)$draft->attribute( 'id' ), 'created' => (int)$draft->attribute( 'created' ), 'modified' => (int)$draft->attribute( 'modified' ) );

        return self::response( $versions );
    }

    protected static function handleShare( $parts )
    {
        $layoutId = isset( $parts[0] ) ? (int)$parts[0] : 0;
        if ( $layoutId <= 0 )
            return self::response( array( 'error' => 'Missing layout id.' ), 400 );

        $layout = expLayoutsLayout::fetch( $layoutId );
        if ( !$layout instanceof expLayoutsLayout )
            return self::response( array( 'error' => 'Layout not found.' ), 404 );

        $db = eZDB::instance();
        if ( !self::ensureShareTable( $db ) )
            return self::response( array( 'error' => 'Share storage is unavailable.' ), 500 );

        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        if ( $method === 'POST' )
        {
            $token = bin2hex( random_bytes( 32 ) );
            $created = time();
            $stored = $db->query( 'INSERT INTO explayouts_share (layout_id, token, created) VALUES ( '
                . $layoutId . ', \''
                . $db->escapeString( $token ) . '\', '
                . $created . ' )' );
            // Answering 201 regardless handed the caller a token that was in
            // nobody's table and would never validate.
            if ( $stored === false )
                return self::response( array( 'error' => 'Share token could not be stored.' ), 500 );

            return self::response( array(
                'layout_id' => $layoutId,
                'share_token' => $token,
                'created' => $created,
            ), 201 );
        }

        if ( $method === 'GET' )
        {
            $tokens = $db->arrayQuery( 'SELECT id, token, created FROM explayouts_share WHERE layout_id = ' . $layoutId . ' ORDER BY created DESC' );
            // A failed read returns false, which would be sent as the JSON
            // body in place of the list the client is documented to receive.
            if ( !is_array( $tokens ) )
                return self::response( array( 'error' => 'Share tokens could not be read.' ), 500 );

            return self::response( $tokens );
        }

        if ( $method === 'DELETE' )
        {
            $token = isset( $parts[1] ) ? $parts[1] : '';
            if ( $token === '' )
                return self::response( array( 'error' => 'Missing token.' ), 400 );

            $removed = $db->query( 'DELETE FROM explayouts_share WHERE layout_id = ' . $layoutId . ' AND token = \'' . $db->escapeString( $token ) . '\'' );
            if ( $removed === false )
                return self::response( array( 'error' => 'Share token could not be removed.' ), 500 );

            return self::response( array( 'deleted' => true ) );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
    }

    /**
     * Create explayouts_share on first use.
     *
     * The table belongs to no schema file, so the endpoint builds it on
     * demand. The DDL was MySQL's alone - int(11), KEY, ENGINE=InnoDB - which
     * every other engine rejects, and nothing checked the result. On SQLite
     * that meant the table was never created, the insert that followed failed
     * too, and the endpoint still answered 201 with a share token it had not
     * stored.
     *
     * SQLite gets its own spelling. MySQL keeps the original, and the MongoDB
     * driver reads the key clauses out of that same statement to build the
     * matching indexes, so both share one definition.
     *
     * The table and its index names must stay identical to the declaration in
     * extension/explayouts/share/db_schema.dba and the per-engine files beside
     * it. The system upgrade page compares the live database against those,
     * and anything created here under a different name is reported as an
     * inconsistency for the operator to correct by hand - which is how the
     * index names below came to be idx_share_layout and idx_share_token
     * rather than something local to this method.
     *
     * @return bool false when the table is not usable, which the caller must
     *              turn into an error response rather than ignore.
     */
    protected static function ensureShareTable( $db )
    {
        static $ready = array();

        $engine = $db->databaseName();
        if ( isset( $ready[$engine] ) )
            return $ready[$engine];

        if ( $engine === 'sqlite' )
        {
            $statements = array(
                'CREATE TABLE IF NOT EXISTS explayouts_share (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    layout_id INTEGER NOT NULL DEFAULT 0,
                    token TEXT NOT NULL,
                    created INTEGER NOT NULL DEFAULT 0 )',
                'CREATE INDEX IF NOT EXISTS idx_share_layout ON explayouts_share ( layout_id )',
                'CREATE UNIQUE INDEX IF NOT EXISTS idx_share_token ON explayouts_share ( token )',
            );
        }
        else
        {
            $statements = array( 'CREATE TABLE IF NOT EXISTS explayouts_share (
                id int(11) NOT NULL AUTO_INCREMENT,
                layout_id int(11) NOT NULL DEFAULT 0,
                token varchar(64) NOT NULL,
                created int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_share_layout (layout_id),
                UNIQUE KEY idx_share_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' );
        }

        foreach ( $statements as $statement )
        {
            if ( $db->query( $statement ) === false )
            {
                eZDebug::writeError( 'Could not create explayouts_share on ' . $engine, __METHOD__ );
                $ready[$engine] = false;
                return false;
            }
        }

        $ready[$engine] = true;
        return true;
    }

    protected static function objectToArray( eZPersistentObject $object )
    {
        $class = get_class( $object );
        $definition = $class::definition();
        $row = array();
        if ( isset( $definition['fields'] ) && is_array( $definition['fields'] ) )
        {
            foreach ( $definition['fields'] as $field => $info )
                $row[$field] = $object->attribute( $field );
        }
        return $row;
    }

    protected static function listToArrays( $list )
    {
        $result = array();
        if ( !is_array( $list ) )
            return $result;
        foreach ( $list as $item )
        {
            if ( $item instanceof eZPersistentObject )
                $result[] = self::objectToArray( $item );
        }
        return $result;
    }

    protected static function rulesForLayout( $layoutId )
    {
        $rules = array();
        $definition = expLayoutsRule::definition();
        $list = eZPersistentObject::fetchObjectList( $definition, null, array( 'layout_id' => (int)$layoutId ) );
        foreach ( $list as $rule )
        {
            $ruleData = self::objectToArray( $rule );
            $ruleData['targets'] = self::listToArrays( expLayoutsRuleTarget::fetchByRule( $rule->attribute( 'id' ) ) );
            $ruleData['conditions'] = self::listToArrays( expLayoutsRuleCondition::fetchByRule( $rule->attribute( 'id' ) ) );
            $rules[] = $ruleData;
        }
        return $rules;
    }

    protected static function response( $data, $status = 200 )
    {
        if ( $status !== 200 )
            http_response_code( $status );

        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = json_encode( $data );
        return $Result;
    }

    protected static function slugify( $text )
    {
        $text = strtolower( trim( $text ) );
        $text = preg_replace( '/[^a-z0-9]+/', '_', $text );
        $text = trim( $text, '_' );
        return $text !== '' ? $text : 'layout_' . time();
    }
}
