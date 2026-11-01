<?php
/**
 * Reading through the layouts API writes nothing for a user without explayouts_ui_api/edit: no zone a layout lacks,
 * no default collection of a block, no share table. Share tokens are listed only with edit.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Opens the protected helpers of the dispatcher to the test. */
class expLayoutsUIApplicationApiProbe extends expLayoutsUIApplicationApi
{
    public static function collectionsOf( $block, $blockInfo ) { return self::blockCollections( $block, $blockInfo ); }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class ApplicationApiReadWritesTest extends TestCase
{
    protected function setUp(): void
    {
        eZUser::$functions = array( 'explayouts_ui_api/read' );
        eZUser::$asked = array();
        foreach ( array( 'expLayoutsLayout', 'expLayoutsZone', 'expLayoutsLayoutType', 'expLayoutsCollection', 'expLayoutsBlock' ) as $standIn )
            $standIn::reset();
        eZDB::$tables = array();
        eZDB::$statements = array();
        $_POST = array();

        // A layout whose type has the zones main and side, of which it has main only.
        expLayoutsLayout::$returns['fetchByIdentifier'] = false;
        expLayoutsLayout::$returns['fetch'] = function ( $id ) { return new expLayoutsLayout( array( 'id' => $id ) ); };
        expLayoutsLayoutType::$returns['getZones'] = array( 'main', 'side' );
        expLayoutsLayoutType::$returns['getGroup'] = 'site';
        expLayoutsZone::$returns['fetchByLayout'] = array();
        expLayoutsZone::$returns['create'] = function ( $layoutId, $identifier, $status ) {
            return new expLayoutsZone( array( 'layout_id' => $layoutId, 'identifier' => $identifier, 'status' => $status ) );
        };
        expLayoutsZone::$returns['resolveSource'] = function ( $zone ) { return $zone; };
        expLayoutsBlock::$returns['fetchByZone'] = array();
        expLayoutsCollection::$returns['fetchByBlock'] = false;
        expLayoutsCollection::$returns['create'] = function ( $blockId, $type ) { return new expLayoutsCollection( array( 'block_id' => $blockId ) ); };
    }

    protected static function layout(): expLayoutsLayout
    {
        return new expLayoutsLayout( array( 'id' => 5, 'status' => 1, 'layout_type' => 'two', 'identifier' => 'l5', 'name' => 'L5' ) );
    }

    public function testReadingALayoutAddsNoMissingZoneWithoutEdit()
    {
        $answer = expLayoutsUIApplicationApi::layoutToArray( self::layout() );

        $this->assertNotContains( 'create', expLayoutsZone::$calls );
        $this->assertNotContains( 'store', expLayoutsZone::$calls );
        $this->assertSame( array(), $answer['zones'], 'the missing zones are left out' );
    }

    public function testReadingALayoutWithEditAddsTheMissingZones()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $answer = expLayoutsUIApplicationApi::layoutToArray( self::layout() );

        $this->assertSame( 2, count( array_keys( expLayoutsZone::$calls, 'create', true ) ) );
        $this->assertSame( 2, count( array_keys( expLayoutsZone::$calls, 'store', true ) ) );
        $this->assertSame( array( 'main', 'side' ), array_column( $answer['zones'], 'identifier' ) );
    }

    public function testReadingABlockCreatesNoDefaultCollectionWithoutEdit()
    {
        $block = new expLayoutsCollection( array( 'id' => 7 ) );
        $this->assertSame( array(), expLayoutsUIApplicationApiProbe::collectionsOf( $block, array( 'has_collection' => true ) ) );
        $this->assertSame( array( 'fetchByBlock' ), expLayoutsCollection::$calls );
    }

    public function testReadingABlockWithEditCreatesTheDefaultCollection()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $block = new expLayoutsCollection( array( 'id' => 7 ) );
        $collections = expLayoutsUIApplicationApiProbe::collectionsOf( $block, array( 'has_collection' => true ) );

        $this->assertSame( 7, $collections[0]['block_id'] );
        $this->assertSame( array( 'fetchByBlock', 'create', 'store' ), expLayoutsCollection::$calls );
    }

    public function testListingShareTokensNeedsEdit()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = expLayoutsUIApplicationApi::handle( array( 'share', '5' ), false );
        $body = json_decode( $result['content'], true );

        $this->assertSame( 403, http_response_code() );
        $this->assertSame( 'access_denied', $body['error']['reason'] );
        $this->assertSame( array(), eZDB::$statements, 'nothing read, nothing created' );
    }

    public function testListingShareTokensWithoutTheTableCreatesNoTable()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = expLayoutsUIApplicationApi::handle( array( 'share', '5' ), false );

        $this->assertSame( array(), json_decode( $result['content'], true ) );
        $this->assertSame( array(), eZDB::$statements, 'no CREATE TABLE on a read' );
    }

    public function testListingShareTokensReadsAnExistingTable()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        eZDB::$tables = array( 'explayouts_share' );
        $_SERVER['REQUEST_METHOD'] = 'GET';
        expLayoutsUIApplicationApi::handle( array( 'share', '5' ), false );

        $this->assertCount( 1, eZDB::$statements );
        $this->assertStringStartsWith( 'SELECT id, token, created FROM explayouts_share', eZDB::$statements[0] );
    }

    public function testMakingAShareTokenCreatesTheTableFirst()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        expLayoutsUIApplicationApi::handle( array( 'share', '5' ), false );

        $this->assertStringStartsWith( 'CREATE TABLE IF NOT EXISTS explayouts_share', trim( eZDB::$statements[0] ) );
        $this->assertStringStartsWith( 'INSERT INTO explayouts_share', end( eZDB::$statements ) );
    }

    public function testTheEditorShowsNoPermissionForARefusedChange()
    {
        $bundle = file_get_contents( dirname( __DIR__ ) . '/design/standard/javascript/netgen-layouts.js' );
        $this->assertStringContainsString( '403===t.status&&("GET"===t.opts.type||/"reason":"access_denied"/.test(t.responseText||"")?(r=nglT("Not allowed")', $bundle );
    }
}
