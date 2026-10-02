<?php
/**
 * The code of extension/explayouts_ui_api/modules/explayouts_ui_api/rules.php, moved into a class (#207 stage 1). The file extension/explayouts_ui_api/modules/explayouts_ui_api/rules.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace
{
if ( !function_exists( 'ruleToArray' ) ) {
function ruleToArray( $rule )
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
}
}

namespace Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi
{

class Rules extends \Exponential\Runnable\ModuleView
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
        $ruleId = isset( $Params['RuleID'] ) ? (int)$Params['RuleID'] : 0;

        \eZDebug::setHandleType( \eZDebug::HANDLE_NONE );
        \eZDebug::instance()->setMessageOutput( 0 );

        $service = new \expLayoutsCoreRuleService();

        if ( $ruleId > 0 )
        {
            $rule = $service->load( $ruleId );
            $response = $rule ? ruleToArray( $rule ) : array( 'error' => 'Rule not found.' );
        }
        else
        {
            $rules = $service->listAll();
            $response = array( 'rules' => array_map( 'ruleToArray', $rules ) );
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
