<?php
/**
 * The editor preview and Design tab of the gallery blocks (gallery, slider, thumb_gallery, grid_gallery, sushi_bar).
 *
 * A gallery item (expLayoutsGalleryBlockHandler) is an array with the node under 'node' and the name as 'alt'. The
 * preview read only 'name' and 'node_id', skipped every such item, and showed "No content" for a gallery with
 * items. A block with items and none to show printed its other values ("total: 0 items: [] thumbnail_size: small").
 * The Design tab dropped the values of the query parameters but kept their fields, so Parent node ID and Limit were
 * shown empty and the next save stored the empty value; and a change of the view type was never saved.
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
if ( !class_exists( 'eZURI', false ) )
{
    class eZURI
    {
        public static function transformURI( &$url ) { return true; }
    }
}
if ( !class_exists( 'eZContentObject', false ) )
{
    class eZContentObject
    {
        public $attributes = array();
        public function __construct( array $attributes = array() ) { $this->attributes = $attributes; }
        public function attribute( $name ) { return isset( $this->attributes[$name] ) ? $this->attributes[$name] : null; }
    }
}
if ( !class_exists( 'eZContentObjectTreeNode', false ) )
{
    class eZContentObjectTreeNode
    {
        public static $fetched = array();
        public $attributes = array();
        public function __construct( array $attributes = array() ) { $this->attributes = $attributes; }
        public function attribute( $name ) { return isset( $this->attributes[$name] ) ? $this->attributes[$name] : null; }
        public static function fetch( $nodeId ) { self::$fetched[] = $nodeId; return null; }
    }
}
// the block's parameters, as the other explayouts stand-ins of the bootstrap (expLayoutsTestStandIn)
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
        public static $info = array( 'name' => 'Grid gallery', 'view_types' => array( 'grid_gallery', 'gallery', 'slider', 'thumb_gallery', 'sushi_bar' ) );
        public static function getBlockInfo( $identifier ) { return self::$info; }
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class GalleryBlockPreviewTest extends TestCase
{
    protected function galleryNode( $nodeId, $name )
    {
        return new eZContentObjectTreeNode( array(
            'node_id' => $nodeId,
            'name' => $name,
            'url_alias' => 'media/images/' . strtolower( $name ),
            'data_map' => array(),
            'object' => new eZContentObject( array( 'content_class' => new eZContentObject( array( 'name' => 'Image' ) ) ) ),
        ) );
    }

    protected function block()
    {
        return new expLayoutsBlock( array( 'id' => 3, 'name' => '', 'definition_identifier' => 'grid_gallery', 'view_type' => 'grid_gallery' ) );
    }

    public function testTheItemsOfAGalleryAreShownByName()
    {
        // The shape expLayoutsGalleryBlockHandler::buildItem() had before it also carried name and node_id.
        expLayoutsRenderer::$prepared = array( 'parameters' => array(), 'values' => array(
            'total' => 2,
            'items' => array(
                array( 'alt' => 'Sunset', 'link' => 'media/images/sunset', 'node' => $this->galleryNode( 21, 'Sunset' ), 'has_image' => false, 'url' => '' ),
                array( 'alt' => 'Harbour', 'link' => 'media/images/harbour', 'node' => $this->galleryNode( 22, 'Harbour' ), 'has_image' => false, 'url' => '' ),
            ),
            'images' => array(),
            'thumbnail_size' => 'small',
        ) );

        $html = expLayoutsUIApplicationApi::renderBlockHtml( $this->block() );

        $this->assertStringNotContainsString( 'block-empty', $html );
        $this->assertStringContainsString( '>Sunset</a>', $html );
        $this->assertStringContainsString( '>Harbour</a>', $html );
        $this->assertStringContainsString( 'Location (Image)', $html );
        $this->assertSame( array(), eZContentObjectTreeNode::$fetched, 'the node the item carries is used, not fetched again' );
    }

    public function testAGalleryWithoutItemsSaysNoContent()
    {
        expLayoutsRenderer::$prepared = array( 'parameters' => array(), 'values' => array(
            'total' => 0,
            'items' => array(),
            'images' => array(),
            'thumbnail_size' => 'small',
        ) );

        $html = expLayoutsUIApplicationApi::renderBlockHtml( $this->block() );

        $this->assertMatchesRegularExpression( '#<p class="block-empty"[^>]*>No content</p>#', $html );
        $this->assertStringNotContainsString( 'thumbnail_size', $html );
        $this->assertStringNotContainsString( 'total:', $html );
    }

    protected function putViewType( $viewType, $storedViewType )
    {
        eZUser::$functions = array( 'explayouts_ui_api/read', 'explayouts_ui_api/edit' );
        expLayoutsBlock::reset();
        $block = new expLayoutsBlock( array( 'id' => 3, 'status' => 1, 'name' => '', 'definition_identifier' => 'grid_gallery', 'view_type' => $storedViewType, 'zone_id' => 0 ) );
        expLayoutsBlock::$returns['fetch'] = $block;
        expLayoutsBlockParameter::$returns['fetchByBlock'] = array();
        expLayoutsCollection::$returns['fetchByBlock'] = null;
        expLayoutsZone::$returns['fetch'] = null;
        expLayoutsRenderer::$prepared = array( 'parameters' => array(), 'values' => array() );
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_POST = array( 'view_type' => $viewType );

        $result = expLayoutsUIApplicationApi::handle( array( 'eng', 'blocks', '3' ) );
        return array( $block, $result );
    }

    public function testAViewTypeTheDefinitionDoesNotHaveIsRefused()
    {
        // The frontend templates of a gallery block include the template named by the view type: any value was
        // stored as given.
        list( $block, $result ) = $this->putViewType( '../title', 'grid_gallery' );

        $this->assertSame( 400, http_response_code() );
        $this->assertStringContainsString( 'Unknown view type', json_encode( $result ) );
        $this->assertSame( 'grid_gallery', $block->attribute( 'view_type' ) );
        $this->assertSame( 0, $block->stored );
    }

    public function testAViewTypeOfTheDefinitionIsSaved()
    {
        list( $block, ) = $this->putViewType( 'slider', 'grid_gallery' );

        $this->assertNotSame( 400, http_response_code() );
        $this->assertSame( 'slider', $block->attribute( 'view_type' ) );
        $this->assertSame( 1, $block->stored );
    }

    public function testTheViewTypeABlockAlreadyHasIsKept()
    {
        // a block stored as 'default' sends it back with every other change
        list( $block, ) = $this->putViewType( 'default', 'default' );

        $this->assertNotSame( 400, http_response_code() );
        $this->assertSame( 'default', $block->attribute( 'view_type' ) );
    }

    public function testTheDesignTabDropsTheFieldsOfTheQueryParametersNotOnlyTheirValues()
    {
        // the fields of both tabs come from expLayoutsUIApplicationApi::blockFormParameters() (ContentTabTest)
        $source = file_get_contents( dirname( __DIR__ ) . '/classes/explayoutsuiapplicationapi.php' );
        $this->assertStringContainsString( "if ( ( in_array( \$name, \$legacyQueryParams, true ) && !\$inContent )", $source );
        $this->assertStringContainsString( 'unset( $parameters[$name] );', $source );
    }

    public function testTheViewTypeSelectIsLabelledByTheViewTypeNames()
    {
        $source = file_get_contents( dirname( __DIR__ ) . '/classes/runnable/views/explayouts_ui_api/app.php' );
        $this->assertStringContainsString( "setVariable( 'view_type_names'", $source );
        $template = file_get_contents( dirname( __DIR__ ) . '/design/standard/templates/explayouts_ui_api/form_block_fields.tpl' );
        $this->assertStringContainsString( '$view_type_names[$view_type]', $template );
    }

    public function testTheDesignTabLeavesOutParametersOfViewTypesTheBlockCannotHave()
    {
        $source = file_get_contents( dirname( __DIR__ ) . '/classes/explayoutsuiapplicationapi.php' );
        $this->assertStringContainsString( "!array_intersect( array_map( 'trim', explode( ',', (string)\$definition['view_type'] ) ), \$viewTypes )", $source );
    }

    public function testAChangedViewTypeIsSaved()
    {
        // The view type select carries js-skip-on-change, so the form's own change handler never saves it; only the
        // master select of a master/slave pair was saved, through the change it triggers on its slave, and not at all
        // when the new view type left no item view type to choose. The master's submit waits for that change
        // (tests/js/view-type-save.test.mjs runs it).
        $bundle = file_get_contents( dirname( __DIR__ ) . '/design/standard/javascript/netgen-layouts.js' );
        // Plain DOM since the editor runs without jQuery (./lib/jquery/view_types): a submit event the form view handles,
        // never a native submit
        $this->assertStringContainsString(
            'function s(e){e&&e.dispatchEvent(new Event("submit",{bubbles:!0,cancelable:!0}))}document.addEventListener("change",function(e){var t=e.target.closest&&e.target.closest(".view-type"),f;t&&(i(t),f=t.closest("form"),t.classList.contains("master")?setTimeout(function(){s(f)},0):s(f))})',
            $bundle
        );
    }

    public function testANewBlockGetsTheFirstViewTypeOfItsDefinition()
    {
        $source = file_get_contents( dirname( __DIR__ ) . '/classes/explayoutsuiapplicationapi.php' );
        $this->assertMatchesRegularExpression( "/\\\$block->setAttribute\\( 'view_type', \\(string\\)reset\\( \\\$blockInfo\\['view_types'\\] \\) \\);/", $source );
    }
}
