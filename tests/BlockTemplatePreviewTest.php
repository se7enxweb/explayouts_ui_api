<?php
/**
 * The template path of the canvas preview (expLayoutsUIApplicationApi::renderBlockTemplatePreview(), through
 * renderBlockHtml()): the block's frontend template is fetched with the block, its output made inert, a template that
 * fails gives the short preview, and the shared engine is left as it was - the errors, warnings and fetched templates
 * gathered before the preview, and its variable 'block'. The engine is a stand-in (tests/fixtures/templatestandin.php)
 * with the kernel's fetch() behaviour; the kernel's own template engine needs the settings and design of an
 * installation, which this kernel-free suite does not have.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class BlockTemplatePreviewTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/templatestandin.php';
        if ( !class_exists( 'ezpI18n', false ) )
            eval( 'class ezpI18n { public static function tr( $context, $source ) { return $source; } }' );
        if ( !class_exists( 'expLayoutsBlockParameter', false ) )
            eval( 'class expLayoutsBlockParameter { use expLayoutsTestStandIn; }' );
        if ( !class_exists( 'expLayoutsRenderer', false ) )
            eval( 'class expLayoutsRenderer { public static $prepared = array(); public static function prepareBlock( $block ) { return self::$prepared; } }' );
        if ( !class_exists( 'expLayoutsBlockHandlerFactory', false ) )
            eval( 'class expLayoutsBlockHandlerFactory { public static function getBlockInfo( $identifier ) { return array( "name" => "About", "view_types" => array( "default" ) ); } }' );

        expLayoutsRenderer::$prepared = array( 'id' => 9, 'parameters' => array(), 'values' => array( 'title' => 'Über <uns>', 'content' => 'Offen.' ) );
    }

    protected function block( $definition )
    {
        return new expLayoutsBlock( array( 'id' => 9, 'name' => '', 'definition_identifier' => $definition, 'view_type' => 'default' ) );
    }

    public function testTheFrontendTemplateIsRenderedWithTheBlockAndMadeInert()
    {
        eZTemplate::$templates['design:explayouts/block/about.tpl'] = function ( $tpl )
        {
            $block = $tpl->variable( 'block' );
            return '<section class="slb slb-about" onclick="x()"><h2>' . htmlspecialchars( $block['values']['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . '</h2>'
                . '<script>alert(1)</script><p>' . htmlspecialchars( $block['values']['content'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . '</p></section>';
        };

        $html = expLayoutsUIApplicationApi::renderBlockHtml( $this->block( 'about' ) );

        $this->assertSame( array( 'design:explayouts/block/about.tpl' ), eZTemplate::$fetched );
        $this->assertStringContainsString( '<div class="block-template-preview"><section class="slb slb-about"><h2>Über &lt;uns&gt;</h2><p>Offen.</p></section></div>', $html );
        $this->assertStringNotContainsString( 'onclick', $html );
        $this->assertStringNotContainsString( '<script', $html );
    }

    public function testAFailingTemplateGivesTheShortPreview()
    {
        // no template for "quote": the engine logs an error
        $html = expLayoutsUIApplicationApi::renderBlockHtml( $this->block( 'quote' ) );

        $this->assertSame( array( 'design:explayouts/block/quote.tpl' ), eZTemplate::$fetched );
        $this->assertStringContainsString( 'block-short-preview', $html );
        $this->assertStringContainsString( '<strong>Über</strong>', $html, 'markup in the title is left out' );
        $this->assertStringNotContainsString( 'block-template-preview', $html );
    }

    public function testADefinitionThatIsNoNameIsNotFetched()
    {
        $html = expLayoutsUIApplicationApi::renderBlockHtml( $this->block( '../../pagelayout' ) );

        $this->assertSame( array(), eZTemplate::$fetched );
        $this->assertStringContainsString( 'block-short-preview', $html );
    }

    public function testTheSharedEngineIsLeftAsItWas()
    {
        $tpl = eZTemplate::factory();
        $tpl->ErrorLog = array( array( 'name' => 'earlier', 'text' => 'an error of the page' ) );
        $tpl->WarningLog = array( array( 'name' => 'earlier', 'text' => 'a warning of the page' ) );
        $tpl->TemplateFetchList = array( 'design:pagelayout.tpl' );
        $tpl->setVariable( 'block', 'the block of the page' );

        // a failing preview (its own error must not stay behind) and a working one
        expLayoutsUIApplicationApi::renderBlockHtml( $this->block( 'quote' ) );
        eZTemplate::$templates['design:explayouts/block/about.tpl'] = function () { return '<p>ok</p>'; };
        expLayoutsUIApplicationApi::renderBlockHtml( $this->block( 'about' ) );

        $this->assertSame( array( array( 'name' => 'earlier', 'text' => 'an error of the page' ) ), $tpl->ErrorLog );
        $this->assertSame( array( array( 'name' => 'earlier', 'text' => 'a warning of the page' ) ), $tpl->WarningLog );
        $this->assertSame( array( 'design:pagelayout.tpl' ), $tpl->TemplateFetchList );
        $this->assertSame( 'the block of the page', $tpl->variable( 'block' ) );

        $fresh = new eZTemplate();
        eZTemplate::$instance = $fresh;
        expLayoutsUIApplicationApi::renderBlockHtml( $this->block( 'about' ) );
        $this->assertFalse( $fresh->hasVariable( 'block' ), 'a variable the engine did not have is removed again' );
        $this->assertSame( array(), $fresh->ErrorLog );
    }
}
