<?php
/**
 * The code of extension/explayouts_ui_api/modules/explayouts_ui_api/layouts.php, moved into a class (#207 stage 1). The file extension/explayouts_ui_api/modules/explayouts_ui_api/layouts.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace
{
if ( !function_exists( 'layoutToArray' ) ) {
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
}
}

namespace Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi
{

class Layouts extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $http = \eZHTTPTool::instance();
        $layoutId = isset( $Params['LayoutID'] ) ? (int)$Params['LayoutID'] : 0;

        \eZDebug::updateSettings( array( 'debug-enabled' => false ) );

        $service = new \expLayoutsCoreLayoutService();

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
        return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
