<?php
/**
 * The editor canvas of a block without a collection (about, hero, quote, video ...) and the translated Design tab.
 *
 * The canvas dumped such a block's values ("title: ... content: ... image_url: ..."). It now shows the block's
 * frontend template, made inert (sanitizePreviewHtml()), and without a template engine or template a short preview
 * of title, text and image (renderBlockShortPreview()). The Design tab printed the parameter names, option texts and
 * view type names in English.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

if ( !class_exists( 'ezpI18n', false ) )
{
    class ezpI18n
    {
        public static function tr( $context, $source ) { return $source; }
    }
}
if ( !class_exists( 'expLayoutsBlockParameter', false ) )
    eval( 'class expLayoutsBlockParameter { use expLayoutsTestStandIn; }' );
if ( !class_exists( 'expLayoutsRenderer', false ) )
{
    class expLayoutsRenderer
    {
        public static $prepared = array();
        public static function prepareBlock( $block ) { return self::$prepared; }
    }
}
if ( !class_exists( 'expLayoutsBlockHandlerFactory', false ) )
{
    class expLayoutsBlockHandlerFactory
    {
        public static $info = array( 'name' => 'About', 'view_types' => array( 'default' ) );
        public static function getBlockInfo( $identifier ) { return self::$info; }
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class BlockCanvasPreviewTest extends TestCase
{
    public static function parsers(): array
    {
        return array( 'HTML5 (\Dom\HTMLDocument where PHP has it)' => array( false ), 'libxml HTML (DOMDocument)' => array( true ) );
    }

    /**
     * @dataProvider parsers
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('parsers')]
    public function testTheFrontendMarkupIsMadeInert( bool $legacyParser )
    {
        $html = expLayoutsUIApplicationApi::sanitizePreviewHtml(
            '<section class="slb slb-about block-content js-toggle" id="x" style="color:red" data-attr="title" onclick="alert(1)">'
            . '<h2>Über uns &amp; <em>mehr</em></h2><script>alert(2)</script><style>p{}</style>'
            . '<a href="javascript:alert(3)">bad</a><a href="/about" data-browser="1">Mehr</a>'
            . '<iframe src="https://www.youtube.com/embed/abc"></iframe>'
            . '<form action="/x"><input name="parameters[title]" value="x"><button class="btn">Go</button></form>'
            . '<img src="/var/a.jpg" alt="A" onerror="alert(4)" width="40"></section>',
            $legacyParser
        );

        $this->assertStringStartsWith( '<div class="block-template-preview">', $html );
        $this->assertStringContainsString( 'Über uns &amp; <em>mehr</em>', $html );
        $this->assertStringContainsString( '<section class="slb slb-about">', $html, 'the editor\'s own classes are dropped' );
        foreach ( array( '<script', '<style', 'alert(', 'onclick', 'onerror', 'style=', 'id=', 'data-', 'javascript:',
                         '<iframe', '<form', '<input', '<button', 'name=', 'parameters[title]', 'js-toggle', 'block-content' ) as $gone )
            $this->assertStringNotContainsString( $gone, $html, $gone );
        $this->assertStringContainsString( '<p class="block-embed">Embedded content: www.youtube.com</p>', $html );
        $this->assertStringContainsString( 'Go', $html, 'the text of a left out element is kept' );
        $this->assertStringContainsString( '<a href="/about" target="_blank" rel="noopener noreferrer">Mehr</a>', $html );
        $this->assertStringContainsString( '<a>bad</a>', $html );
        $this->assertStringContainsString( '<img src="/var/a.jpg" alt="A" width="40">', $html );
    }

    public static function bypasses(): array
    {
        $cases = array(
            'svg onload' => '<svg onload="alert(1)"><a href="javascript:alert(1)">x</a></svg><p>t</p>',
            'math mglyph style' => '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)></style></mglyph></table></mtext></math><p>t</p>',
            'svg foreignObject' => '<svg><foreignObject><img src=x onerror=alert(1)></foreignObject></svg><p>t</p>',
            // <p>t</p> first: a parser with scripting off keeps the rest inside <noscript>, which goes with its content
            'noscript mutation' => '<p>t</p><noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>',
            'title mutation' => '<title><img src=x onerror=alert(1)></title><p>t</p>',
            'textarea mutation' => '<textarea><img src=x onerror=alert(1)></textarea><p>t</p>',
            'xmp mutation' => '<xmp><img src=x onerror=alert(1)></xmp><p>t</p>',
            'noembed mutation' => '<noembed><img src=x onerror=alert(1)></noembed><p>t</p>',
            'noframes mutation' => '<noframes><img src=x onerror=alert(1)></noframes><p>t</p>',
            'plaintext' => '<p>t</p><plaintext><img src=x onerror=alert(1)>',
            'mixed case scheme' => '<a href="JaVaScRiPt:alert(1)">x</a><p>t</p>',
            'entity scheme' => '<a href="&#106;avascript:alert(1)">x</a><a href="java&#x09;script:alert(1)">y</a><p>t</p>',
            'data svg image' => '<img src="data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9YWxlcnQoMSk+"><a href="data:text/html,<script>alert(1)</script>">x</a><p>t</p>',
            'base href' => '<base href="javascript:alert(1)//"><a href="/x">x</a><p>t</p>',
            'clobbering' => '<form name="attributes"><input name="attributes"></form><img name="getElementById" src="/a.jpg" id="app"><a name="CKEDITOR" id="ace" href="/y">y</a><p>t</p>',
            'comment' => '<!--><img src=x onerror=1>--><p>t</p><!-- <img src=x onerror=alert(1)> -->',
            'cdata' => '<![CDATA[<img src=x onerror=alert(1)>]]><p>t</p>',
            'protocol relative and backslash' => '<a href="//evil.example/">a</a><a href="/\\evil.example/">b</a><img src="//evil.example/a.png"><p>t</p>',
            'event on allowed element' => '<p onmouseover="alert(1)" style="x:expression(alert(1))">t</p><details open ontoggle="alert(1)"><summary>s</summary></details>',
        );
        $data = array();
        foreach ( $cases as $name => $html )
        {
            $data[$name . ', HTML5'] = array( $html, false );
            $data[$name . ', libxml'] = array( $html, true );
        }
        return $data;
    }

    /**
     * @dataProvider bypasses
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bypasses')]
    public function testNoBypassReachesTheEditor( string $markup, bool $legacyParser )
    {
        $html = expLayoutsUIApplicationApi::sanitizePreviewHtml( $markup, $legacyParser );

        // every case carries a harmless <p>t</p>: the markup was parsed and rebuilt, not just refused
        $this->assertStringContainsString( '<p>t</p>', $html );
        $this->assertDoesNotMatchRegularExpression( '/\son[a-z]+\s*=/i', $html, 'no event handler' );
        $this->assertStringNotContainsStringIgnoringCase( 'javascript:', $html );
        $this->assertStringNotContainsStringIgnoringCase( 'data:', $html );
        $this->assertStringNotContainsString( 'evil.example', preg_replace( '/>[^<]*</', '><', $html ), 'no foreign address in an attribute' );
        $this->assertDoesNotMatchRegularExpression( '/\s(name|id|style|ontoggle)=/i', $html );

        // only allowed elements, every attribute allowed for its element; and the output reads back the same
        $allowed = array( 'div', 'span', 'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'b', 'i', 'u', 'small',
            'blockquote', 'cite', 'figure', 'figcaption', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'a', 'img', 'table', 'thead',
            'tbody', 'tr', 'th', 'td', 'code', 'pre', 'section', 'article', 'header', 'footer', 'details', 'summary', 'label' );
        preg_match_all( '#<([a-zA-Z][a-zA-Z0-9]*)([^>]*)>#', $html, $tags, PREG_SET_ORDER );
        foreach ( $tags as $tag )
        {
            $this->assertContains( strtolower( $tag[1] ), $allowed, $tag[0] );
            preg_match_all( '/\s([a-zA-Z-]+)="/', $tag[2], $attributes );
            foreach ( $attributes[1] as $attribute )
                $this->assertContains( $attribute, array( 'class', 'title', 'href', 'target', 'rel', 'src', 'alt', 'width', 'height', 'colspan', 'rowspan' ), $tag[0] );
        }
        if ( $html !== '' )
            $this->assertSame( $html, expLayoutsUIApplicationApi::sanitizePreviewHtml( substr( $html, strlen( '<div class="block-template-preview">' ), -6 ), $legacyParser ) === ''
                ? $html
                : '<div class="block-template-preview">' . substr( expLayoutsUIApplicationApi::sanitizePreviewHtml( substr( $html, strlen( '<div class="block-template-preview">' ), -6 ), $legacyParser ), strlen( '<div class="block-template-preview">' ), -6 ) . '</div>',
                'sanitizing the output again changes nothing' );
    }

    public function testAddresses()
    {
        $this->assertSame( 'https://example.org/a', expLayoutsUIApplicationApi::previewUrl( 'https://example.org/a' ) );
        $this->assertSame( '/media/a', expLayoutsUIApplicationApi::previewUrl( ' /media/a ' ) );
        $this->assertSame( '#top', expLayoutsUIApplicationApi::previewUrl( '#top' ) );
        $this->assertSame( '', expLayoutsUIApplicationApi::previewUrl( '#top', true ) );
        foreach ( array( 'javascript:alert(1)', 'JAVASCRIPT:x', "java\tscript:x", 'data:image/png;base64,AAAA', '//evil.example/',
                      '/\\evil.example', 'vbscript:x', 'page.html', 'mailto:a@b.c', "https://a\nb", 'https:///x' ) as $refused )
            $this->assertSame( '', expLayoutsUIApplicationApi::previewUrl( $refused ), $refused );
    }

    public function testMarkupWithNothingVisibleGivesNothing()
    {
        $this->assertSame( '', expLayoutsUIApplicationApi::sanitizePreviewHtml( '' ) );
        $this->assertSame( '', expLayoutsUIApplicationApi::sanitizePreviewHtml( '<div class="slb slb-spacer"> </div><script>x()</script>' ) );
        $this->assertSame( '', expLayoutsUIApplicationApi::sanitizePreviewHtml( '<div class="slb slb-spacer"> </div><script>x()</script>', true ) );
    }

    public function testTheShortPreviewShowsTitleTextAndImageEscapedAndShortened()
    {
        $html = expLayoutsUIApplicationApi::renderBlockShortPreview( array(
            'title' => 'Hero <b>"Titel"</b>',
            'content' => '<p>' . str_repeat( 'Wort ', 80 ) . '</p>',
            'image_url' => '/var/site/storage/images/hero.jpg',
            'css_class' => 'not-shown',
        ) );

        $this->assertStringContainsString( '<img src="/var/site/storage/images/hero.jpg"', $html );
        $this->assertStringContainsString( '<strong>Hero &quot;Titel&quot;</strong>', $html );
        $this->assertStringContainsString( "\u{2026}</p>", $html );
        $this->assertStringNotContainsString( '<p>Wort', $html );
        $this->assertStringNotContainsString( 'not-shown', $html );
        $this->assertStringNotContainsString( 'title:', $html );
    }

    public function testTheShortPreviewTakesNoImageThatIsNoAddress()
    {
        $html = expLayoutsUIApplicationApi::renderBlockShortPreview( array( 'title' => 'T', 'image_url' => 'javascript:alert(1)' ) );
        $this->assertStringNotContainsString( '<img', $html );
        $this->assertSame( '', expLayoutsUIApplicationApi::renderBlockShortPreview( array( 'title' => '', 'css_class' => 'x', 'level' => 2 ) ) );
    }

    public function testTheCanvasShowsAPreviewInsteadOfTheValues()
    {
        // No template engine in this suite: the short preview.
        expLayoutsRenderer::$prepared = array( 'parameters' => array(), 'values' => array(
            'title' => 'Über uns', 'content' => 'Wir sind offen.', 'image_url' => '', 'css_class' => '',
        ) );
        $block = new expLayoutsBlock( array( 'id' => 9, 'name' => '', 'definition_identifier' => 'about', 'view_type' => 'default' ) );

        $html = expLayoutsUIApplicationApi::renderBlockHtml( $block );

        $this->assertStringContainsString( '<strong>Über uns</strong>', $html );
        $this->assertStringContainsString( 'Wir sind offen.', $html );
        $this->assertStringNotContainsString( 'block-value', $html );
        $this->assertStringNotContainsString( 'title:', $html );
        $this->assertStringNotContainsString( 'block-empty', $html );
    }

    public function testABlockWithEmptyValuesSaysNoContent()
    {
        expLayoutsRenderer::$prepared = array( 'parameters' => array(), 'values' => array( 'title' => '', 'height' => 0 ) );
        $block = new expLayoutsBlock( array( 'id' => 9, 'name' => '', 'definition_identifier' => 'spacer', 'view_type' => 'default' ) );

        $html = expLayoutsUIApplicationApi::renderBlockHtml( $block );

        $this->assertMatchesRegularExpression( '#<p class="block-empty"[^>]*>No content</p>#', $html );
        $this->assertStringNotContainsString( 'height:', $html );
    }

    public function testTheDesignTabTranslatesNamesOptionsAndViewTypeNames()
    {
        $templates = dirname( __DIR__ ) . '/design/standard/templates/explayouts_ui_api/';
        $parameter = file_get_contents( $templates . 'form_block_parameter.tpl' );
        $this->assertStringNotContainsString( '{$param.name|wash()}', $parameter );
        $this->assertStringNotContainsString( '{$opt_label|wash()}', $parameter );
        $this->assertSame( 9, substr_count( $parameter, "{\$param.name|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}" ) );
        $this->assertSame( 2, substr_count( $parameter, "{\$opt_label|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}" ) );
        $this->assertStringContainsString( "{\$view_type_names[\$view_type]|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}",
            file_get_contents( $templates . 'form_block_fields.tpl' ) );
    }

    public function testEveryCatalogueTranslatesEveryParameterText()
    {
        $sources = function ( $file, &$translations = null )
        {
            $doc = new DOMDocument();
            $this->assertTrue( $doc->load( $file ), $file );
            $xpath = new DOMXPath( $doc );
            $list = array();
            $translations = array();
            foreach ( $xpath->query( "//context[name='design/standard/explayouts_ui_api/parameters']/message" ) as $message )
            {
                $source = $xpath->query( 'source', $message )->item( 0 )->nodeValue;
                $list[] = $source;
                $translations[$source] = $xpath->query( 'translation', $message )->item( 0 );
            }
            return $list;
        };

        $reference = $sources( dirname( __DIR__ ) . '/translations/eng-US/translation.ts' );
        foreach ( array( 'Thumbnails per row/slide', 'Enable lightbox', 'Image attribute identifier', 'Thumbnail size alias',
                         'Use vertical whitespace', 'Wrap in container', 'Grid gallery', 'Sushi bar', 'Regular' ) as $text )
            $this->assertContains( $text, $reference );
        $this->assertSame( count( $reference ), count( array_unique( $reference ) ), 'no source twice' );

        foreach ( glob( dirname( __DIR__ ) . '/translations/*/translation.ts' ) as $file )
        {
            $language = basename( dirname( $file ) );
            $this->assertSame( $reference, $sources( $file, $translations ), $language );
            if ( $language === 'untranslated' || $language === 'eng-US' )
                continue;
            foreach ( $translations as $source => $translation )
            {
                $this->assertNotSame( 'unfinished', $translation->getAttribute( 'type' ), "$language: $source" );
                $this->assertNotSame( '', trim( $translation->nodeValue ), "$language: $source" );
            }
        }
    }
}
