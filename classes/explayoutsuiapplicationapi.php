<?php
class expLayoutsUIApplicationApi
{
    public static function handle( $parts )
    {
        eZDebug::updateSettings( array( 'debug-enabled' => false ) );
        eZDebug::setHandleType( eZDebug::HANDLE_NONE );
        header( 'Content-Type: application/json' );

        $resource = isset( $parts[0] ) ? $parts[0] : '';

        if ( $resource === 'config' )
            return self::handleConfig( array_slice( $parts, 1 ) );

        $layoutsIndex = array_search( 'layouts', $parts );
        $blocksIndex = array_search( 'blocks', $parts );

        if ( $layoutsIndex !== false )
        {
            $subResource = isset( $parts[$layoutsIndex + 2] ) ? $parts[$layoutsIndex + 2] : '';
            if ( $subResource === 'blocks' )
                return self::handleBlocks( $parts, $layoutsIndex );

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

    protected static function blockTypes()
    {
        $group = 'standard';
        $groupBlockTypes = array();
        $blockTypes = array();

        foreach ( expLayoutsBlockHandlerFactory::getAvailableBlocks() as $identifier )
        {
            $info = expLayoutsBlockHandlerFactory::getBlockInfo( $identifier );
            if ( !$info )
                continue;

            $groupBlockTypes[] = (string)$identifier;
            $blockTypes[] = array(
                'identifier' => (string)$identifier,
                'name' => (string)$info['name'],
                'definition_identifier' => (string)$identifier,
                'group_name' => $group,
                'enabled' => true,
                'is_container' => !empty( $info['has_collection'] ),
                'icon' => '',
                'parameters' => '{}',
                'defaults' => array(),
            );
        }

        return self::response( array(
            'block_types' => $blockTypes,
            'block_type_groups' => array(
                array(
                    'identifier' => $group,
                    'name' => 'Standard',
                    'enabled' => true,
                    'block_types' => $groupBlockTypes,
                ),
            ),
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
            $layout = $service->load( $id );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );
            $layout->publish();
            return self::response( self::layoutToArray( $service->load( $id ) ) );
        }

        if ( $id > 0 && $sub === 'draft' && $method === 'DELETE' )
        {
            $layout = $service->load( $id );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );
            $layout->setAttribute( 'status', 2 );
            $layout->setAttribute( 'modified', time() );
            $layout->store();
            return self::response( self::layoutToArray( $layout ), 200 );
        }

        if ( $id > 0 && $sub === 'draft' && $method === 'POST' )
        {
            $layout = $service->load( $id );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );

            if ( (int)$layout->attribute( 'status' ) === 2 )
            {
                expLayoutsLayout::removeDraft( (string)$layout->attribute( 'identifier' ) );
                $layout->setAttribute( 'status', 1 );
                $layout->setAttribute( 'modified', time() );
                $layout->store();
            }

            return self::response( self::layoutToArray( $layout ), 201 );
        }

        if ( $id > 0 )
        {
            $layout = $service->load( $id );
            return self::response( $layout ? self::layoutToArray( $layout ) : array( 'error' => 'Layout not found.' ), $layout ? 200 : 404 );
        }

        $layouts = $service->listAll();
        return self::response( array( 'values' => array_map( array( __CLASS__, 'layoutToArray' ), $layouts ), 'total' => count( $layouts ) ) );
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
            $layout = $service->load( $layoutId );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );

            $zoneObject = null;
            foreach ( expLayoutsZone::fetchByLayout( $layoutId, null ) as $z )
            {
                if ( (string)$z->attribute( 'identifier' ) === $zone )
                {
                    $zoneObject = $z;
                    break;
                }
            }
            if ( !$zoneObject )
                return self::response( array( 'error' => 'Zone not found.' ), 404 );

            $block = expLayoutsBlock::create(
                (int)$zoneObject->attribute( 'id' ),
                $layoutId,
                $definitionIdentifier,
                isset( $_POST['name'] ) ? trim( $_POST['name'] ) : ''
            );
            $block->setAttribute( 'position', 0 );
            $block->store();

            return self::response( self::blockToArray( $block ), 201 );
        }

        $layout = expLayoutsLayout::fetch( $layoutId );
        if ( !$layout )
            return self::response( array( 'values' => array(), 'total' => 0 ) );

        $zoneBlocks = array();
        $zones = expLayoutsZone::fetchByLayout( $layoutId, null );
        foreach ( $zones as $zone )
        {
            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), null );
            foreach ( $blocks as $block )
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

            $layout = expLayoutsLayout::fetch( $layoutId );
            if ( !$layout )
                return self::response( array( 'error' => 'Layout not found.' ), 404 );

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

            $block = expLayoutsBlock::create(
                (int)$zoneObject->attribute( 'id' ),
                $layoutId,
                $definitionIdentifier,
                isset( $data['name'] ) ? trim( $data['name'] ) : ''
            );
            $block->setAttribute( 'status', (int)$layout->attribute( 'status' ) );
            $block->setAttribute( 'position', isset( $data['parent_position'] ) ? (int)$data['parent_position'] : 0 );
            $block->store();

            self::saveBlockParameters( $block, isset( $data['parameters'] ) && is_array( $data['parameters'] ) ? $data['parameters'] : array() );

            return self::response( self::blockToArray( $block ), 201 );
        }

        $sub = isset( $parts[$blocksIndex + 2] ) ? $parts[$blocksIndex + 2] : '';

        if ( $id > 0 )
        {
            $block = expLayoutsBlock::fetch( $id );
            if ( !$block )
                return self::response( array( 'error' => 'Block not found.' ), 404 );

            if ( $method === 'DELETE' )
            {
                self::deleteBlock( $block );
                return self::response( array( 'id' => $id ), 200 );
            }

            if ( $method === 'POST' && $sub === 'copy' )
            {
                $data = self::requestData();
                $newBlock = expLayoutsBlock::create(
                    (int)$block->attribute( 'zone_id' ),
                    (int)$block->attribute( 'layout_id' ),
                    (string)$block->attribute( 'definition_identifier' ),
                    (string)$block->attribute( 'name' )
                );
                $newBlock->setAttribute( 'view_type', (string)$block->attribute( 'view_type' ) );
                $newBlock->setAttribute( 'position', isset( $data['parent_position'] ) ? (int)$data['parent_position'] : (int)$block->attribute( 'position' ) );
                $newBlock->setAttribute( 'status', (int)$block->attribute( 'status' ) );
                $newBlock->store();

                foreach ( expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) ) as $param )
                {
                    expLayoutsBlockParameter::set( (int)$newBlock->attribute( 'id' ), (string)$param->attribute( 'name' ), (string)$param->attribute( 'value' ) );
                }

                return self::response( self::blockToArray( $newBlock ), 201 );
            }

            if ( $method === 'POST' && $sub === 'move' )
            {
                $data = self::requestData();
                if ( isset( $data['zone_identifier'] ) && isset( $data['layout_id'] ) )
                {
                    $zone = self::findZoneByIdentifier( (int)$data['layout_id'], trim( $data['zone_identifier'] ) );
                    if ( $zone )
                    {
                        $block->setAttribute( 'layout_id', (int)$data['layout_id'] );
                        $block->setAttribute( 'zone_id', (int)$zone->attribute( 'id' ) );
                    }
                }
                if ( isset( $data['parent_position'] ) )
                    $block->setAttribute( 'position', (int)$data['parent_position'] );
                elseif ( isset( $data['position'] ) )
                    $block->setAttribute( 'position', (int)$data['position'] );
                $block->setAttribute( 'modified', time() );
                $block->store();

                return self::response( self::blockToArray( $block ) );
            }

            if ( in_array( $method, array( 'PUT', 'PATCH', 'POST' ) ) )
            {
                $data = self::requestData();
                if ( isset( $data['name'] ) )
                    $block->setAttribute( 'name', trim( $data['name'] ) );
                if ( isset( $data['view_type'] ) )
                    $block->setAttribute( 'view_type', trim( $data['view_type'] ) );
                if ( isset( $data['position'] ) )
                    $block->setAttribute( 'position', (int)$data['position'] );
                $block->setAttribute( 'modified', time() );
                $block->store();

                self::saveBlockParameters( $block, isset( $data['parameters'] ) && is_array( $data['parameters'] ) ? $data['parameters'] : array() );
            }

            return self::response( self::blockToArray( $block ) );
        }

        return self::response( array( 'error' => 'Unknown block request.' ), 404 );
    }

    public static function renderBlockHtml( $block )
    {
        $name = (string)$block->attribute( 'name' );
        $prepared = expLayoutsRenderer::prepareBlock( $block );
        $values = is_array( $prepared ) && isset( $prepared['values'] ) ? $prepared['values'] : array();
        $definition = (string)$block->attribute( 'definition_identifier' );

        if ( $name === '' )
        {
            $name = ucwords( str_replace( '_', ' ', $definition ) );
        }

        $content = '';
        if ( $definition === 'title' && !empty( $values['title'] ) )
        {
            $level = isset( $values['level'] ) ? max( 1, min( 6, (int)$values['level'] ) ) : 2;
            $content .= '<h' . $level . '>' . htmlspecialchars( (string)$values['title'] ) . '</h' . $level . '>';
        }
        elseif ( $definition === 'text' && !empty( $values['content'] ) )
        {
            $content .= '<p>' . nl2br( htmlspecialchars( (string)$values['content'] ) ) . '</p>';
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

        if ( trim( strip_tags( $content ) ) === '' )
        {
            $content = '<p class="block-empty" style="color:#888; font-style:italic;">No content</p>';
        }

        return '<div class="block-header"><span class="name">' . htmlspecialchars( $name ) . '</span></div><div class="block-content">' . $content . '</div>';
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

        $block->remove();
    }

    protected static function saveBlockParameters( $block, $parameters )
    {
        if ( empty( $parameters ) )
            return;

        $blockId = (int)$block->attribute( 'id' );
        foreach ( $parameters as $name => $value )
        {
            expLayoutsBlockParameter::set( $blockId, trim( $name ), is_scalar( $value ) ? (string)$value : json_encode( $value ) );
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
        $isContainer = is_array( $blockInfo ) && !empty( $blockInfo['has_collection'] );

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
            'is_container' => $isContainer,
            'has_published_state' => false,
            'parameters' => $parameters,
            'html' => self::renderBlockHtml( $block ),
            'collections' => array(),
        );
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
            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), null );
            foreach ( $blocks as $block )
            {
                $blockIds[] = (int)$block->attribute( 'id' );
            }

            $zones[] = array(
                'identifier' => $zoneIdentifier,
                'name' => $zoneIdentifier,
                'block_ids' => $blockIds,
            );

            $zoneHtmlParts[] = '<div class="zone" data-zone="' . htmlspecialchars( $zoneIdentifier, ENT_QUOTES, 'UTF-8' ) . '"></div>';
        }

        $zonesWithAllowed = array();
        foreach ( $zones as $zone )
        {
            $zone['allowed_block_definitions'] = true;
            $zone['linked_layout_id'] = null;
            $zone['linked_zone_identifier'] = null;
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
            'shared' => false,
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
                    $zone->setAttribute( 'linked_layout_id', (int)$zoneData['linked_layout_id'] );
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
                $priority = isset( $ruleData['priority'] ) ? (int)$ruleData['priority'] : 0;
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

    protected static function handleCollections( $parts )
    {
        $id = isset( $parts[0] ) ? (int)$parts[0] : 0;
        $sub = isset( $parts[1] ) ? $parts[1] : '';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';

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

    protected static function handleCollectionItems( $collectionId, $parts )
    {
        $itemId = isset( $parts[0] ) ? (int)$parts[0] : 0;
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
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
        $db->query( 'CREATE TABLE IF NOT EXISTS explayouts_share (
            id int(11) NOT NULL AUTO_INCREMENT,
            layout_id int(11) NOT NULL DEFAULT 0,
            token varchar(64) NOT NULL,
            created int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY layout_id (layout_id),
            UNIQUE KEY token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' );

        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        if ( $method === 'POST' )
        {
            $token = bin2hex( random_bytes( 32 ) );
            $created = time();
            $db->query( 'INSERT INTO explayouts_share (layout_id, token, created) VALUES ( '
                . $layoutId . ', \''
                . $db->escapeString( $token ) . '\', '
                . $created . ' )' );
            return self::response( array(
                'layout_id' => $layoutId,
                'share_token' => $token,
                'created' => $created,
            ), 201 );
        }

        if ( $method === 'GET' )
        {
            $tokens = $db->arrayQuery( 'SELECT id, token, created FROM explayouts_share WHERE layout_id = ' . $layoutId . ' ORDER BY created DESC' );
            return self::response( $tokens );
        }

        if ( $method === 'DELETE' )
        {
            $token = isset( $parts[1] ) ? $parts[1] : '';
            if ( $token === '' )
                return self::response( array( 'error' => 'Missing token.' ), 400 );
            $db->query( 'DELETE FROM explayouts_share WHERE layout_id = ' . $layoutId . ' AND token = \'' . $db->escapeString( $token ) . '\'' );
            return self::response( array( 'deleted' => true ) );
        }

        return self::response( array( 'error' => 'Unsupported method.' ), 405 );
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
