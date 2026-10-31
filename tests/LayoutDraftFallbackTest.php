<?php
/**
 * GET layouts/<id>?published=false: the draft, or a 404 when there is none, as callers of the upstream layouts API
 * expect. With fallback=published (what the editor asks since 1.3.31) a layout without a draft answers with its
 * published version instead, so opening such a layout no longer costs a request the browser logs as failed.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\TestCase;

/** Opens loadLayoutForApi() to the test. */
class expLayoutsUIApplicationApiDraftProbe extends expLayoutsUIApplicationApi
{
    public static function loadForApi( $service, $id, $fallback = 'load' ) { return self::loadLayoutForApi( $service, $id, $fallback ); }
}

/** A layout service with or without a draft; records what was asked. */
class LayoutDraftFallbackTestService
{
    public $asked = array();
    private $hasDraft;
    public function __construct( $hasDraft ) { $this->hasDraft = $hasDraft; }
    public function loadDraft( $id ) { $this->asked[] = 'loadDraft'; return $this->hasDraft ? 'draft ' . $id : false; }
    public function loadPublished( $id ) { $this->asked[] = 'loadPublished'; return 'published ' . $id; }
    public function load( $id ) { $this->asked[] = 'load'; return 'any ' . $id; }
}

class LayoutDraftFallbackTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = array();
    }

    public function testWithoutTheFallbackALayoutWithoutDraftStaysNotFound()
    {
        $_GET = array( 'published' => 'false' );
        $service = new LayoutDraftFallbackTestService( false );
        $this->assertFalse( expLayoutsUIApplicationApiDraftProbe::loadForApi( $service, 7 ) );
        $this->assertSame( array( 'loadDraft' ), $service->asked );
    }

    public function testWithTheFallbackALayoutWithoutDraftGivesThePublishedVersion()
    {
        $_GET = array( 'published' => 'false', 'fallback' => 'published' );
        $service = new LayoutDraftFallbackTestService( false );
        $this->assertSame( 'published 7', expLayoutsUIApplicationApiDraftProbe::loadForApi( $service, 7 ) );
        $this->assertSame( array( 'loadDraft', 'loadPublished' ), $service->asked );
    }

    public function testWithTheFallbackADraftIsStillTheDraft()
    {
        $_GET = array( 'published' => 'false', 'fallback' => 'published' );
        $service = new LayoutDraftFallbackTestService( true );
        $this->assertSame( 'draft 7', expLayoutsUIApplicationApiDraftProbe::loadForApi( $service, 7 ) );
        $this->assertSame( array( 'loadDraft' ), $service->asked );
    }

    public function testOtherFallbackValuesAreIgnored()
    {
        $_GET = array( 'published' => 'false', 'fallback' => 'anything' );
        $this->assertFalse( expLayoutsUIApplicationApiDraftProbe::loadForApi( new LayoutDraftFallbackTestService( false ), 7 ) );
    }
}
