<?php
/**
 * The Content and Design tabs of the block sidebar, as in the reference (netgen/layouts-core: parameters of the group
 * "content" in the content form, of the group "design" in the design form). explayouts names a definition's content
 * parameters in [BlockDefinition_<id>] ContentParameters[]; expLayoutsUIApplicationApi::blockFormParameters() splits
 * the parameters by it. Also: a select keeps a stored value that is none of its options.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

if ( !class_exists( 'expLayoutsBlockHandlerFactory', false ) )
{
    class expLayoutsBlockHandlerFactory
    {
        public static $info = array();
        public static $handler = null;
        public static function getBlockInfo( $identifier ) { return self::$info; }
        public static function get( $identifier ) { return self::$handler; }
    }
}
if ( !class_exists( 'expLayoutsStandardDesignParameters', false ) )
{
    class expLayoutsStandardDesignParameters
    {
        public static function get()
        {
            return array( 'css_class' => array( 'name' => 'CSS class', 'type' => 'string', 'default' => '' ) );
        }
    }
}

class ctHandler
{
    public function getParameters()
    {
        return array(
            'title' => array( 'name' => 'Title', 'type' => 'string', 'default' => '' ),
            'tag' => array( 'name' => 'HTML tag', 'type' => 'select', 'options' => array( 'h1' => 'Heading 1' ), 'default' => '' ),
            'use_link' => array( 'name' => 'Use link', 'type' => 'compound_checkbox', 'default' => '',
                'children' => array( 'link' => array( 'name' => 'Link', 'type' => 'string', 'default' => '' ) ) ),
            'node_id' => array( 'name' => 'Node ID', 'type' => 'integer', 'default' => 0 ),
            'limit' => array( 'name' => 'Limit', 'type' => 'integer', 'default' => 10 ),
            'columns' => array( 'name' => 'Columns', 'type' => 'select', 'options' => array(), 'view_type' => 'grid' ),
        );
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class ContentTabTest extends TestCase
{
    protected function setUp(): void
    {
        expLayoutsBlockHandlerFactory::$handler = new ctHandler();
        expLayoutsBlockHandlerFactory::$info = array( 'view_types' => array( 'default' ), 'view_type_names' => array(),
            'content_parameters' => array( 'title', 'use_link', 'node_id' ) );
        expLayoutsBlockParameter::reset();
        expLayoutsBlockParameter::$returns['fetchByBlock'] = array(
            new expLayoutsBlockParameter( array( 'name' => 'title', 'value' => 'Hello' ) ),
            new expLayoutsBlockParameter( array( 'name' => 'link', 'value' => '/x' ) ),
            new expLayoutsBlockParameter( array( 'name' => 'tag', 'value' => 'h2' ) ),
        );
    }

    protected function block()
    {
        return new expLayoutsBlock( array( 'id' => 5, 'definition_identifier' => 'title', 'view_type' => 'default' ) );
    }

    public function testTheContentTabHasTheContentParametersWithTheirValues()
    {
        $form = expLayoutsUIApplicationApi::blockFormParameters( $this->block(), 'content' );
        $this->assertSame( array( 'title', 'use_link', 'node_id' ), array_keys( $form['parameters'] ) );
        $this->assertSame( 'Hello', $form['values']['title'] );
        $this->assertSame( '/x', $form['values']['link'], 'the child of a compound parameter goes with its parent' );
        $this->assertArrayNotHasKey( 'tag', $form['values'] );
    }

    public function testTheDesignTabHasTheRestAndTheStandardDesignParameters()
    {
        $form = expLayoutsUIApplicationApi::blockFormParameters( $this->block(), 'design' );
        // limit is a legacy query parameter (the query form edits it), columns is for a view type the block lacks
        $this->assertSame( array( 'tag', 'css_class' ), array_keys( $form['parameters'] ) );
        $this->assertSame( 'h2', $form['values']['tag'] );
        $this->assertArrayNotHasKey( 'title', $form['values'] );
    }

    public function testADefinitionWithoutContentParametersKeepsAllInTheDesignTab()
    {
        expLayoutsBlockHandlerFactory::$info = array( 'view_types' => array( 'default' ) );
        $this->assertSame( array(), expLayoutsUIApplicationApi::blockFormParameters( $this->block(), 'content' )['parameters'] );
        $this->assertSame( array( 'title', 'tag', 'use_link', 'css_class' ),
            array_keys( expLayoutsUIApplicationApi::blockFormParameters( $this->block(), 'design' )['parameters'] ) );
    }

    public function testTheContentFormDrawsItsParametersAndOpensFirstWithThem()
    {
        $dir = dirname( __DIR__ ) . '/design/standard/templates/explayouts_ui_api/';
        $content = file_get_contents( $dir . 'form_block_content.tpl' );
        $this->assertStringContainsString( "{include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=\$param_name", $content );
        $this->assertStringContainsString( 'name="name"', $content );
        $edit = file_get_contents( $dir . 'form_block_edit.tpl' );
        $this->assertStringContainsString( '<li role="presentation"{if $content_first} class="active"{/if}><a href="#" id="tab-content"', $edit );
        $this->assertStringContainsString( '<div class="tab-pane{if $content_first|not} active{/if}" id="tab-design-tab"', $edit );
        $view = file_get_contents( dirname( __DIR__ ) . '/classes/runnable/views/explayouts_ui_api/app.php' );
        $this->assertStringContainsString( "blockFormParameters( \$block, 'content' )", $view );
        $this->assertStringContainsString( "blockFormParameters( \$block, 'design' )", $view );
    }

    public function testASelectKeepsAStoredValueThatIsNoneOfItsOptions()
    {
        $template = file_get_contents( dirname( __DIR__ ) . '/design/standard/templates/explayouts_ui_api/form_block_parameter.tpl' );
        $this->assertStringContainsString( '<option value="{$value|wash()}" selected="selected" class="saved-value">', $template );
        $this->assertStringContainsString( "'(saved) %value'|i18n( 'design/standard/explayouts_ui_api/form_block_parameter'", $template );
        $this->assertLessThan( strpos( $template, '{foreach $param.options as $opt_val => $opt_label}' . "\n" . '                <option' ),
            strpos( $template, 'class="saved-value"' ), 'the stored value comes before the options' );
        foreach ( glob( dirname( __DIR__ ) . '/translations/*/translation.ts' ) as $file )
            $this->assertStringContainsString( '<source>(saved) %value</source>', file_get_contents( $file ), basename( dirname( $file ) ) );
    }
}
