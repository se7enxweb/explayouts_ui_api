<?php
/**
 * The siteaccess whose block settings the editor reads, per request (expLayoutsUIApplicationApi::
 * previewSiteAccessOfRequest()): the site of the layout the request is about (se7enxweb/exponential#364), the
 * administration's preview siteaccess for a request about no layout.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\TestCase;

if ( !class_exists( 'expLayoutsPreviewSiteAccess', false ) )
{
    /** explayouts' class: layout 7 belongs to the site starter_ger, the administration previews in site. */
    class expLayoutsPreviewSiteAccess
    {
        public static $asked = array();
        public static function resolve() { self::$asked[] = 'resolve'; return 'site'; }
        public static function resolveForLayout( $layoutId ) { self::$asked[] = 'resolveForLayout ' . $layoutId; return $layoutId === 7 ? 'starter_ger' : 'site'; }
    }
}

class PreviewSiteAccessOfRequestTest extends TestCase
{
    protected function setUp(): void
    {
        expLayoutsPreviewSiteAccess::$asked = array();
        expLayoutsBlock::reset();
        // block 41 lies in layout 7
        expLayoutsBlock::$returns['fetch'] = function( $id ) { return $id === 41 ? new expLayoutsBlock( array( 'layout_id' => 7 ) ) : null; };
    }

    public function testTheLayoutInThePath()
    {
        $this->assertSame( 7, expLayoutsUIApplicationApi::layoutIdOfRequest( array( 'layouts', '7', 'blocks' ) ) );
        $this->assertSame( 'starter_ger', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( array( 'layouts', '7' ) ) );
        $this->assertSame( array( 'resolveForLayout 7' ), expLayoutsPreviewSiteAccess::$asked );
    }

    public function testTheLayoutOfABlock()
    {
        $this->assertSame( 7, expLayoutsUIApplicationApi::layoutIdOfRequest( array( 'blocks', '41', 'edit' ) ) );
        $this->assertSame( 'starter_ger', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( array( 'blocks', '41' ) ) );
        // the block forms of the Design and Content tabs, below app/ and not app/api/
        foreach ( array( array( 'eng', 'blocks', '41', 'edit' ), array( 'eng', 'blocks', '41', 'form' ), array( 'eng', 'blocks', '41', 'form', 'edit', 'content' ) ) as $parts )
            $this->assertSame( 'starter_ger', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( $parts ), implode( '/', $parts ) );
    }

    public function testTheLayoutOfTheQueryForTheBlockTypes()
    {
        $this->assertSame( 7, expLayoutsUIApplicationApi::layoutIdOfRequest( array( 'config', 'block_types' ), array( 'layout' => '7' ) ) );
        $this->assertSame( 'starter_ger', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( array( 'config', 'block_types' ), array( 'layout' => '7' ) ) );
    }

    public function testThePathsOfTheEditorGiveTheirParts()
    {
        $this->assertSame( array( 'layouts', '7' ), expLayoutsUIApplicationApi::requestParts( 'api/layouts/7' ) );
        $this->assertSame( array( 'eng', 'blocks', '41', 'form' ), expLayoutsUIApplicationApi::requestParts( 'eng/blocks/41/form' ), 'a block form, not below api/' );
        $this->assertSame( array(), expLayoutsUIApplicationApi::requestParts( '' ), 'the editor\'s page' );
        $this->assertSame( 'starter_ger', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( expLayoutsUIApplicationApi::requestParts( 'eng/blocks/41/form' ) ) );
    }

    public function testNoLayoutGivesThePreviewSiteAccessOfTheAdministration()
    {
        foreach ( array( array( 'config', 'block_types' ), array( 'layouts', 'shared' ), array( 'blocks', '99' ), array( 'rules' ), array() ) as $parts )
            $this->assertSame( 0, expLayoutsUIApplicationApi::layoutIdOfRequest( $parts, array( 'layout' => 'x' ) ), implode( '/', $parts ) );
        $this->assertSame( 'site', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( array( 'rules' ) ) );
        $this->assertSame( 'site', expLayoutsUIApplicationApi::previewSiteAccessOfRequest( array(), array( 'layout' => '7' ) ), 'the editor\'s page itself' );
        $this->assertSame( array( 'resolve', 'resolve' ), expLayoutsPreviewSiteAccess::$asked );
    }
}
