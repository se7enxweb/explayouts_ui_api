<?php
$Module = array( 'name' => 'explayouts_ui_api',
                 'variable_params' => true );

$ViewList = array();

$ViewList['layouts'] = array(
    'script' => 'layouts.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'ezsetupnavigationpart',
    'params' => array( 'LayoutID' )
);

$ViewList['rules'] = array(
    'script' => 'rules.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'ezsetupnavigationpart',
    'params' => array( 'RuleID' )
);

$ViewList['blocks'] = array(
    'script' => 'blocks.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'ezsetupnavigationpart',
    'params' => array( 'ZoneID' )
);

$ViewList['app'] = array(
    'script' => 'app.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'ezsetupnavigationpart',
    'params' => array( )
);

$FunctionList = array();
$FunctionList['read'] = array();
