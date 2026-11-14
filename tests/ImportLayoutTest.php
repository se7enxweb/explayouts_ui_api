<?php
/**
 * The import of a layout (POST transfer/import): always a draft with draft zones and blocks, rules disabled and only
 * with known types, layout type and block definitions of the settings, one transaction; both export forms (this
 * API's, parameters as a name/value list, and the one of explayouts' layout list, version 1 with a parameter map).
 * The rule forms of the API keep only known target and condition types.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

// The block definitions as a recording stand-in (see expLayoutsTestStandIn in the bootstrap); a test of another file
// may bring its own.
if ( !class_exists( 'expLayoutsBlockHandlerFactory', false ) )
    eval( 'class expLayoutsBlockHandlerFactory { use expLayoutsTestStandIn; }' );

/** Opens the protected import to the test. */
class expLayoutsUIApplicationApiImportProbe extends expLayoutsUIApplicationApi
{
    public static function import( $data, &$error = null ) { return self::importLayout( $data, $error ); }
    public static function targets( $data ) { return self::normalizeRuleTargets( $data ); }
    public static function conditions( $data ) { return self::normalizeRuleConditions( $data ); }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class ImportLayoutTest extends TestCase
{
    /** what was stored, as class => list of attributes */
    public static $stored = array();
    public static $parameters = array();

    protected function setUp(): void
    {
        foreach ( array( 'expLayoutsLayout', 'expLayoutsZone', 'expLayoutsLayoutType', 'expLayoutsCollection', 'expLayoutsBlock',
                         'expLayoutsBlockParameter', 'expLayoutsCollectionItem', 'expLayoutsBlockHandlerFactory', 'expLayoutsRule',
                         'expLayoutsRuleTarget', 'expLayoutsRuleCondition' ) as $standIn )
            $standIn::reset();
        eZDB::$statements = array();
        self::$stored = array();
        self::$parameters = array();

        $record = function ( $class ) {
            return function ( ...$arguments ) use ( $class ) {
                $object = new $class( array( 'id' => 100 + count( ImportLayoutTest::$stored ) ) );
                $object->attributes["__arguments"] = $arguments;
                ImportLayoutTest::$stored[] = array( $class, $object );
                return $object;
            };
        };
        expLayoutsLayout::$returns['fetchByIdentifier'] = false;
        expLayoutsLayout::$returns['create'] = $record( 'expLayoutsLayout' );
        expLayoutsZone::$returns['create'] = $record( 'expLayoutsZone' );
        expLayoutsBlock::$returns['create'] = $record( 'expLayoutsBlock' );
        expLayoutsCollection::$returns['create'] = $record( 'expLayoutsCollection' );
        expLayoutsCollectionItem::$returns['create'] = $record( 'expLayoutsCollectionItem' );
        expLayoutsRule::$returns['create'] = $record( 'expLayoutsRule' );
        expLayoutsRuleTarget::$returns['create'] = $record( 'expLayoutsRuleTarget' );
        expLayoutsRuleCondition::$returns['create'] = $record( 'expLayoutsRuleCondition' );
        expLayoutsBlockParameter::$returns['set'] = function ( $blockId, $name, $value ) { ImportLayoutTest::$parameters[$name] = $value; };
        expLayoutsLayoutType::$returns['getTypeInfo'] = function ( $type ) { return $type === 'layout_1' ? array( 'zones' => array( 'main' ) ) : false; };
        expLayoutsBlockHandlerFactory::$returns['getBlockInfo'] = function ( $definition ) {
            return in_array( $definition, array( 'title', 'list' ), true ) ? array( 'view_types' => array( 'title', 'title_big' ) ) : false;
        };
    }

    protected static function storedOf( $class )
    {
        return array_values( array_map( function ( $row ) { return $row[1]; },
            array_filter( self::$stored, function ( $row ) use ( $class ) { return $row[0] === $class; } ) ) );
    }

    /** An export of this API: status published, a rule enabled with a catch-all and an unknown target */
    protected static function apiExport()
    {
        return array(
            'id' => 9, 'identifier' => 'home', 'name' => 'Home', 'layout_type' => 'layout_1', 'status' => 2,
            'zones' => array( array( 'identifier' => 'main', 'status' => 2, 'blocks' => array(
                array( 'definition_identifier' => 'title', 'status' => 2, 'view_type' => 'title_big',
                       'parameters' => array( array( 'name' => 'title', 'value' => 'Hi' ) ), 'collection' => array() ),
            ) ) ),
            'rules' => array( array( 'enabled' => 1, 'priority' => 50,
                'targets' => array( array( 'target_type' => 'path_info_prefix', 'target_value' => '/' ), array( 'target_type' => 'evil', 'target_value' => 'x' ) ),
                'conditions' => array( array( 'condition_type' => 'sitacess', 'condition_value' => '["x"]' ) ) ) ),
        );
    }

    public function testAnImportIsADraftWithDisabledRulesOfKnownTypes()
    {
        $error = '';
        $layout = expLayoutsUIApplicationApiImportProbe::import( self::apiExport(), $error );

        $this->assertInstanceOf( expLayoutsLayout::class, $layout, $error );
        $this->assertSame( 1, $layout->attribute( 'status' ) );
        $this->assertSame( 1, self::storedOf( 'expLayoutsZone' )[0]->attributes["__arguments"][2], 'the zone is a draft' );
        $this->assertSame( 1, self::storedOf( 'expLayoutsBlock' )[0]->attribute( 'status' ), 'the block is a draft' );
        $this->assertSame( 'title_big', self::storedOf( 'expLayoutsBlock' )[0]->attribute( 'view_type' ) );
        $this->assertSame( array( 'title' => 'Hi' ), self::$parameters );
        $this->assertSame( array(), self::storedOf( 'expLayoutsCollection' ), 'an empty collection list makes no collection' );
        $this->assertSame( 0, self::storedOf( 'expLayoutsRule' )[0]->attribute( 'enabled' ), 'the rule is disabled' );
        $this->assertSame( array( 'path_info_prefix' ), array_map( function ( $t ) { return $t->attributes["__arguments"][1]; }, self::storedOf( 'expLayoutsRuleTarget' ) ) );
        $this->assertSame( array(), self::storedOf( 'expLayoutsRuleCondition' ), 'an unknown condition is left out' );
        $this->assertSame( array( 'BEGIN', 'COMMIT' ), eZDB::$statements );
    }

    public function testTheExportOfExplayoutsLayoutListImportsToo()
    {
        $layout = expLayoutsUIApplicationApiImportProbe::import( array(
            'version' => 1, 'identifier' => 'home', 'name' => 'Home', 'layout_type' => 'layout_1', 'shared' => false,
            'zones' => array( array( 'id' => 3, 'identifier' => 'main', 'position' => 0, 'blocks' => array(
                array( 'definition_identifier' => 'title', 'view_type' => 'title', 'parameters' => array( 'title' => 'Hello', 'level' => '2' ), 'collection' => false ),
            ) ) ),
        ) );
        $this->assertInstanceOf( expLayoutsLayout::class, $layout );
        $this->assertSame( array( 'title' => 'Hello', 'level' => '2' ), self::$parameters );
    }

    public function testUnknownLayoutTypesBlocksAndVersionsImportNothing()
    {
        foreach ( array(
            array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => '../evil' ),
            array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1', 'zones' => array( array( 'identifier' => 'main', 'blocks' => array( array( 'definition_identifier' => 'nope' ) ) ) ) ),
            array( 'version' => 2, 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1' ),
            array( 'identifier' => array( 'x' ), 'name' => 'X', 'layout_type' => 'layout_1' ),
        ) as $export )
        {
            $error = '';
            $this->assertFalse( expLayoutsUIApplicationApiImportProbe::import( $export, $error ) );
            $this->assertNotSame( '', $error );
        }
        $this->assertSame( array(), self::$stored );
        $this->assertSame( array(), eZDB::$statements, 'refused before a transaction' );
    }

    public function testAFailureHalfwayRollsBack()
    {
        expLayoutsBlock::$returns['create'] = function () { throw new RuntimeException( 'Lost connection' ); };
        $error = '';
        $this->assertFalse( expLayoutsUIApplicationApiImportProbe::import( self::apiExport(), $error ) );
        $this->assertSame( array( 'BEGIN', 'ROLLBACK' ), eZDB::$statements );
    }

    public function testAFailedCommitIsAFailedImport()
    {
        eZDB::$commitAnswer = false;
        $error = '';
        $this->assertFalse( expLayoutsUIApplicationApiImportProbe::import( self::apiExport(), $error ) );
        $this->assertSame( 'Import failed.', $error );
        $this->assertSame( array( 'BEGIN', 'COMMIT' ), eZDB::$statements, 'no rollback after the commit' );
    }

    public function testAnErrorHalfwayRollsBackToo()
    {
        expLayoutsBlock::$returns['create'] = function () { throw new TypeError( 'Broken value' ); };
        $error = '';
        $this->assertFalse( expLayoutsUIApplicationApiImportProbe::import( self::apiExport(), $error ) );
        $this->assertSame( array( 'BEGIN', 'ROLLBACK' ), eZDB::$statements );
    }

    public function testAZoneLinksASharedLayoutByItsIdentifierOnly()
    {
        expLayoutsLayout::$returns['fetchByIdentifier'] = function ( $identifier ) {
            return $identifier === 'shared_header' ? new expLayoutsLayout( array( 'id' => 77 ) ) : false;
        };
        $layout = expLayoutsUIApplicationApiImportProbe::import( array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1', 'zones' => array(
            array( 'identifier' => 'header', 'linked_layout_id' => 7 ),
            array( 'identifier' => 'footer', 'linked_layout_id' => 7, 'linked_layout_identifier' => 'shared_header' ) ) ) );
        $this->assertInstanceOf( expLayoutsLayout::class, $layout );
        $zones = self::storedOf( 'expLayoutsZone' );
        $this->assertNull( $zones[0]->attribute( 'linked_layout_id' ), 'a row id alone links nothing' );
        $this->assertSame( 77, $zones[1]->attribute( 'linked_layout_id' ) );
    }

    public function testABlockFieldOfTheWrongKindImportsNothing()
    {
        $error = '';
        $this->assertFalse( expLayoutsUIApplicationApiImportProbe::import( array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1',
            'zones' => array( array( 'identifier' => 'main', 'blocks' => array( array( 'definition_identifier' => 'title', 'name' => array( 'x' ) ) ) ) ) ), $error ) );
        $this->assertStringContainsString( 'name', $error );
        $this->assertSame( array(), self::$stored );
    }

    public function testReturnToIsAPathOfThisSiteOnly()
    {
        foreach ( array( '/content/view/full/2', '/admin/explayouts_ui/dashboard?x=1' ) as $kept )
            $this->assertSame( $kept, expLayoutsUIApplicationApi::safeReturnTo( $kept ) );
        foreach ( array( 'javascript:alert(1)', 'https://evil.example/', '//evil.example/x', '/\\evil.example', "/x\nLocation: y", 'content/1', '', array( '/x' ) ) as $refused )
            $this->assertSame( '', expLayoutsUIApplicationApi::safeReturnTo( $refused ), json_encode( $refused ) );
    }

    public function testTheRuleFormsKeepOnlyKnownTypes()
    {
        $data = array( 'targets' => array( array( 'type' => 'path', 'value' => 'news' ), array( 'type' => 'evil', 'value' => 'x' ), array( 'type' => array( 'path' ) ) ),
                       'conditions' => array( array( 'condition_type' => 'time', 'condition_value' => '{}' ), array( 'type' => 'sitacess', 'value' => 'x' ) ) );
        $this->assertSame( array( array( 'type' => 'path', 'value' => 'news' ) ), expLayoutsUIApplicationApiImportProbe::targets( $data ) );
        $this->assertSame( array( array( 'type' => 'time', 'value' => '{}' ) ), expLayoutsUIApplicationApiImportProbe::conditions( $data ) );
    }
}
