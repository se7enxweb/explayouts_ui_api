<?php
eZDebug::updateSettings( array( 'debug-enabled' => false ) );
$http = eZHTTPTool::instance();
$zoneId = isset( $Params['ZoneID'] ) ? (int)$Params['ZoneID'] : 0;

eZDebug::setHandleType( eZDebug::HANDLE_NONE );
eZDebug::instance()->setMessageOutput( 0 );

if ( $zoneId <= 0 )
{
    $response = array( 'error' => 'Zone ID is required.' );
}
else
{
    $service = new expLayoutsCoreBlockService();
    $blocks = $service->loadByZone( $zoneId );
    $response = array( 'blocks' => array_map( 'blockToArray', $blocks ) );
}

header( 'Content-Type: application/json' );

$Result = array();
$Result['pagelayout'] = false;
$Result['content'] = json_encode( $response );
return $Result;

function blockToArray( $block )
{
    if ( !$block )
        return null;

    $params = array();
    foreach ( expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) ) as $param )
    {
        $params[(string)$param->attribute( 'name' )] = (string)$param->attribute( 'value' );
    }

    return array(
        'id' => (int)$block->attribute( 'id' ),
        'zone_id' => (int)$block->attribute( 'zone_id' ),
        'layout_id' => (int)$block->attribute( 'layout_id' ),
        'name' => (string)$block->attribute( 'name' ),
        'definition_identifier' => (string)$block->attribute( 'definition_identifier' ),
        'view_type' => (string)$block->attribute( 'view_type' ),
        'position' => (int)$block->attribute( 'position' ),
        'parameters' => $params,
    );
}
