<?php
eZDebug::updateSettings( array( 'debug-enabled' => false ) );
$http = eZHTTPTool::instance();
$ruleId = isset( $Params['RuleID'] ) ? (int)$Params['RuleID'] : 0;

eZDebug::setHandleType( eZDebug::HANDLE_NONE );
eZDebug::instance()->setMessageOutput( 0 );

$service = new expLayoutsCoreRuleService();

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
return $Result;

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
