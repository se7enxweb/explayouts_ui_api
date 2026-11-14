<?php
/**
 * The editor's own files (block type icons, favicons) and the preview images of collection items when the
 * installation is served from a sub path (/sub/path here).
 *
 * The block type icons were given as /extension/..., the preview images as /var/..., and app.tpl named the favicons
 * the same way: the browser asked the server root for them, where an installation in a sub path has none of them.
 * They now carry the web root as the template operator ezroot gives it. The favicon manifest named the path of the
 * Symfony bundle of Netgen Layouts (/bundles/netgenlayoutsadmin/...), which no Exponential installation has; it now
 * names its icons beside it.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Declares the stand-ins of the kernel classes the helpers call. setUp() calls it, so they exist only in the process
 * of each test (RunTestsInSeparateProcesses), never in the process that collects the tests: declared at the top of
 * this file they replaced the stand-ins of tests loaded after it (the eZURI of GalleryBlockPreviewTest).
 *
 * eZURI::transformURI() of the kernel puts eZSys::wwwDir() in front when its second argument is true (ezroot), else
 * the index dir with the siteaccess (ezurl), and leaves an address with a scheme alone. The stand-in does the same
 * with a fixed web root and records each call.
 */
function editorAssetUrlTestStandIns()
{
    if ( !class_exists( 'eZURI', false ) )
    {
        class eZURI
        {
            public static $wwwDir = '/sub/path';
            public static $calls = array();
            public static function transformURI( &$href, $ignoreIndexDir = false, $serverURL = null, $htmlEscape = true )
            {
                self::$calls[] = array( $href, $ignoreIndexDir, $serverURL, $htmlEscape );
                if ( strpos( $href, 'http://' ) === 0 || strpos( $href, 'https://' ) === 0 )
                    return false;
                if ( $href === '' || $href[0] !== '/' )
                    $href = '/' . $href;
                $href = ( $ignoreIndexDir ? self::$wwwDir : self::$wwwDir . '/index.php/admin' ) . $href;
                return true;
            }
        }
    }
    if ( !class_exists( 'eZClusterFileHandler', false ) )
    {
        class eZClusterFileHandler
        {
            /** paths fileExists() knows, as the image alias list stores them */
            public static $existing = array();
            public static function instance() { return new self(); }
            public function fileExists( $path ) { return in_array( $path, self::$existing, true ); }
        }
    }
}

/** An image attribute's alias handler: the stored alias list, and imageAlias() for a variation not in it. */
class EditorAssetUrlTestAliasHandler
{
    public $aliasList = array();
    public $generated = array();
    public function aliasList() { return $this->aliasList; }
    public function imageAlias( $alias ) { return isset( $this->generated[$alias] ) ? $this->generated[$alias] : null; }
}

/** The protected helpers, as the API calls them. */
class EditorAssetUrlTestApi extends expLayoutsUIApplicationApi
{
    public static function icon( $identifier ) { return self::blockIcon( $identifier ); }
    public static function iconMap() { return self::blockIconMap(); }
    public static function aliasUrl( $handler, array $aliases ) { return self::imageAliasUrl( $handler, $aliases ); }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class EditorAssetUrlTest extends TestCase
{
    protected function setUp(): void
    {
        editorAssetUrlTestStandIns();
    }

    public function testBlockTypeIconsCarryTheWebRoot()
    {
        $base = '/sub/path/extension/explayouts_ui_api/design/standard/images/';
        $this->assertSame( $base . 'blocks/icon-title.svg', EditorAssetUrlTestApi::icon( 'title' ) );
        $this->assertSame( $base . 'components/icon-component-quote.svg', EditorAssetUrlTestApi::icon( 'exp_component_quote' ) );
        $this->assertSame( $base . 'components/icon-component-generic.svg', EditorAssetUrlTestApi::icon( 'no_such_block' ) );
        // as ezroot('no'): the web root, no host, no HTML escaping (the bundle escapes the value itself)
        foreach ( eZURI::$calls as $call )
            $this->assertSame( array( true, 'relative', false ), array_slice( $call, 1 ) );
    }

    public function testEveryBlockTypeIconIsAFileOfTheExtension()
    {
        $dir = dirname( __DIR__ ) . '/design/standard/images/';
        foreach ( EditorAssetUrlTestApi::iconMap() + array( 'fallback' => expLayoutsUIApplicationApi::BLOCK_ICON_FALLBACK ) as $identifier => $file )
            $this->assertFileExists( $dir . $file, $identifier );
    }

    public function testPreviewImagesCarryTheWebRoot()
    {
        eZClusterFileHandler::$existing = array( 'var/site/storage/images/a/1-1-eng-GB/a_small.jpg', 'var/site/storage/images/b/2-1-eng-GB/b_medium.jpg' );

        // from the stored alias list, which keeps the path relative to the web root and without a leading slash
        $handler = new EditorAssetUrlTestAliasHandler();
        $handler->aliasList = array( 'small' => array( 'url' => 'var/site/storage/images/a/1-1-eng-GB/a_small.jpg' ) );
        $this->assertSame( '/sub/path/var/site/storage/images/a/1-1-eng-GB/a_small.jpg',
                           EditorAssetUrlTestApi::aliasUrl( $handler, array( 'small', 'medium' ) ) );

        // a variation imageAlias() has just written
        $handler = new EditorAssetUrlTestAliasHandler();
        $handler->generated = array( 'medium' => array( 'url' => 'var/site/storage/images/b/2-1-eng-GB/b_medium.jpg' ) );
        $this->assertSame( '/sub/path/var/site/storage/images/b/2-1-eng-GB/b_medium.jpg',
                           EditorAssetUrlTestApi::aliasUrl( $handler, array( 'small', 'medium' ) ) );

        // no variation on disk: no image
        $handler = new EditorAssetUrlTestAliasHandler();
        $handler->aliasList = array( 'small' => array( 'url' => 'var/site/storage/images/c/c_small.jpg' ) );
        $this->assertSame( '', EditorAssetUrlTestApi::aliasUrl( $handler, array( 'small' ) ) );
    }

    public function testAnImageOnAnotherServerStaysAsItIs()
    {
        eZClusterFileHandler::$existing = array( 'https://images.example.org/a_small.jpg' );
        $handler = new EditorAssetUrlTestAliasHandler();
        $handler->aliasList = array( 'small' => array( 'url' => 'https://images.example.org/a_small.jpg' ) );
        $this->assertSame( 'https://images.example.org/a_small.jpg', EditorAssetUrlTestApi::aliasUrl( $handler, array( 'small' ) ) );
    }

    public function testTheEditorPageNamesNoFileFromTheServerRoot()
    {
        $template = (string)file_get_contents( dirname( __DIR__ ) . '/design/standard/templates/explayouts_ui_api/app.tpl' );
        $this->assertNotSame( '', $template );
        $named = 0;
        foreach ( array( 'href="', 'src="', 'content="', 'action="' ) as $attribute )
        {
            $offset = 0;
            while ( ( $at = strpos( $template, $attribute, $offset ) ) !== false )
            {
                $start = $at + strlen( $attribute );
                $end = strpos( $template, '"', $start );
                $this->assertNotFalse( $end, $attribute . ' at ' . $at . ' is not closed' );
                $value = substr( $template, $start, $end - $start );
                $this->assertStringStartsNotWith( '/', $value, $attribute . $value . '"' );
                if ( strpos( $value, '|ezroot(' ) !== false )
                    ++$named;
                $offset = $end + 1;
            }
        }
        // the seven favicon files, each through ezroot
        $this->assertSame( 7, $named );
    }

    public function testTheFaviconManifestNamesItsIconsBesideIt()
    {
        $dir = dirname( __DIR__ ) . '/design/standard/images/favicon/';
        $manifest = json_decode( (string)file_get_contents( $dir . 'manifest.json' ), true );
        $this->assertIsArray( $manifest );
        $this->assertNotEmpty( $manifest['icons'] );
        // a relative src is read against the address of the manifest (Web App Manifest, "src" member)
        foreach ( $manifest['icons'] as $icon )
        {
            $this->assertStringStartsNotWith( '/', $icon['src'] );
            $this->assertStringNotContainsString( '://', $icon['src'] );
            $this->assertFileExists( $dir . $icon['src'] );
        }
    }
}
