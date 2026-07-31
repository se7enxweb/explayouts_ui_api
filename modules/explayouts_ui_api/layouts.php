<?php
$http = eZHTTPTool::instance();
$layoutId = isset( $Params['LayoutID'] ) ? (int)$Params['LayoutID'] : 0;

eZDebug::updateSettings( array( 'debug-enabled' => false ) );

$service = new expLayoutsCoreLayoutService();

if ( $layoutId > 0 )
{
    $layout = $service->load( $layoutId );
    $response = $layout ? layoutToArray( $layout ) : array( 'error' => 'Layout not found.' );
}
else
{
    $layouts = $service->listAll();
    $response = array( 'layouts' => array_map( 'layoutToArray', $layouts ) );
}

header( 'Content-Type: application/json' );

$Result = array();
$Result['pagelayout'] = false;
$Result['content'] = json_encode( $response );
return $Result;

function layoutToArray( $layout )
{
    if ( !$layout )
        return null;

    return array(
        'id' => (int)$layout->attribute( 'id' ),
        'identifier' => (string)$layout->attribute( 'identifier' ),
        'name' => (string)$layout->attribute( 'name' ),
        'layout_type' => (string)$layout->attribute( 'layout_type' ),
        'status' => (int)$layout->attribute( 'status' ),
        'created' => (int)$layout->attribute( 'created' ),
        'modified' => (int)$layout->attribute( 'modified' ),
    );
}
