<?php
/**
 * The code of extension/explayouts_ui_api/modules/explayouts_ui_api/blocks.php, moved into a class (#207 stage 1). The file extension/explayouts_ui_api/modules/explayouts_ui_api/blocks.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace
{
if ( !function_exists( 'blockToArray' ) ) {
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
}
}

namespace Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi
{

class Blocks extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        \eZDebug::updateSettings( array( 'debug-enabled' => false ) );
        $http = \eZHTTPTool::instance();
        $zoneId = isset( $Params['ZoneID'] ) ? (int)$Params['ZoneID'] : 0;

        \eZDebug::setHandleType( \eZDebug::HANDLE_NONE );
        \eZDebug::instance()->setMessageOutput( 0 );

        if ( $zoneId <= 0 )
        {
            $response = array( 'error' => 'Zone ID is required.' );
        }
        else
        {
            $service = new \expLayoutsCoreBlockService();
            $blocks = $service->loadByZone( $zoneId );
            $response = array( 'blocks' => array_map( 'blockToArray', $blocks ) );
        }

        header( 'Content-Type: application/json' );

        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = json_encode( $response );
        return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
