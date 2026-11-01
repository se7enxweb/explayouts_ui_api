<?php
/**
 * The JSON API of the layouts editor: every request that changes something needs explayouts_ui_api/edit, and a
 * failing request shows the client the error's message, file and line only with debug output on.
 *
 * Each test runs in its own process: handle() sends headers and a status code, which a process that has already
 * written output cannot do.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class ApplicationApiAccessTest extends TestCase
{
    protected function setUp(): void
    {
        eZUser::$functions = array( 'explayouts_ui_api/read' );
        eZUser::$asked = array();
        eZDebug::$enabled = false;
        eZLog::$lines = array();
        foreach ( array( 'expLayoutsLayout', 'expLayoutsZone', 'expLayoutsLayoutType', 'expLayoutsCollection', 'expLayoutsBlock' ) as $standIn )
            $standIn::reset();
        eZDB::$tables = array();
        eZDB::$statements = array();
        $_POST = array();
        $_SERVER['REQUEST_URI'] = '/explayouts_ui_api/app/api/layouts';
    }

    /** A layout export, as transfer/import takes it. In a test it arrives in $_POST: php://input is empty here. */
    protected static function importPayload(): array
    {
        return array( 'identifier' => 'imported', 'name' => 'Imported', 'layout_type' => '1_column', 'zones' => array() );
    }

    public static function importMethodsOtherThanPost(): array
    {
        return array( 'GET' => array( 'GET' ), 'HEAD' => array( 'HEAD' ), 'OPTIONS' => array( 'OPTIONS' ), 'PUT' => array( 'PUT' ), 'DELETE' => array( 'DELETE' ) );
    }

    /**
     * @dataProvider importMethodsOtherThanPost
     */
    #[DataProvider( 'importMethodsOtherThanPost' )]
    public function testAnImportNeedsPostAndImportsNothingOtherwise( string $method )
    {
        // edit is given, so only the method can stop it
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = self::importPayload();
        $result = expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );
        $body = json_decode( $result['content'], true );

        $this->assertSame( 405, http_response_code() );
        $this->assertSame( 'method_not_allowed', $body['error']['reason'] );
        $this->assertSame( 'This action needs POST.', $body['message'] );
        $this->assertSame( array(), expLayoutsLayout::$calls, 'nothing was imported' );
    }

    public function testAGetImportOfAReadOnlyUserIsRefusedAndImportsNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = self::importPayload();
        $result = expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        $this->assertSame( 405, http_response_code() );
        $this->assertSame( 405, json_decode( $result['content'], true )['error']['code'] );
        $this->assertSame( array(), expLayoutsLayout::$calls, 'nothing was imported' );
    }

    public function testAPostImportOfAReadOnlyUserIsRefusedAndImportsNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = self::importPayload();
        $result = expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        $this->assertSame( 403, http_response_code() );
        $this->assertSame( 'access_denied', json_decode( $result['content'], true )['error']['reason'] );
        $this->assertSame( array(), expLayoutsLayout::$calls, 'nothing was imported' );
    }

    public function testAPostImportWithEditReachesTheLayouts()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = self::importPayload();
        expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        // the import checks the layout type against the settings first: the request got as far as the layouts
        $this->assertSame( array( 'getTypeInfo' ), expLayoutsLayoutType::$calls );
    }

    public static function changingMethods(): array
    {
        return array( 'POST' => array( 'POST' ), 'PUT' => array( 'PUT' ), 'PATCH' => array( 'PATCH' ), 'DELETE' => array( 'DELETE' ), 'lower case' => array( 'post' ) );
    }

    /**
     * @dataProvider changingMethods
     */
    #[DataProvider( 'changingMethods' )]
    public function testAChangeWithoutEditIsRefusedBeforeAnythingIsTouched( string $method )
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        // 'layouts' would reach the layout service, whose stand-in throws: an answer other than the refusal
        // would be its 500.
        $result = expLayoutsUIApplicationApi::handle( array( 'layouts' ) );
        $body = json_decode( $result['content'], true );

        $this->assertSame( 403, http_response_code() );
        $this->assertSame( 403, $body['error']['code'] );
        $this->assertSame( 'access_denied', $body['error']['reason'] );
        $this->assertSame( 'You are not allowed to change layouts.', $body['message'], 'a top-level message for the editor\'s error dialog' );
        $this->assertSame( array( 'explayouts_ui_api/edit' ), eZUser::$asked );
        $this->assertSame( array(), eZLog::$lines, 'nothing failed behind the refusal' );
    }

    public static function readingMethods(): array
    {
        return array( 'GET' => array( 'GET' ), 'HEAD' => array( 'HEAD' ), 'OPTIONS' => array( 'OPTIONS' ) );
    }

    /**
     * @dataProvider readingMethods
     */
    #[DataProvider( 'readingMethods' )]
    public function testReadingNeedsNoEdit( string $method )
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $result = expLayoutsUIApplicationApi::handle( array( 'nothing_here' ) );

        $this->assertSame( array( 'error' => 'Unknown resource.' ), json_decode( $result['content'], true ) );
        $this->assertSame( 404, http_response_code() );
        $this->assertSame( array(), eZUser::$asked );
    }

    public function testAChangeWithEditGoesOn()
    {
        eZUser::$functions[] = 'explayouts_ui_api/edit';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = expLayoutsUIApplicationApi::handle( array( 'nothing_here' ) );

        $this->assertSame( array( 'error' => 'Unknown resource.' ), json_decode( $result['content'], true ) );
        $this->assertSame( 404, http_response_code() );
    }

    public function testTheModuleDefinesEditAndTheViewsAskForRead()
    {
        $Module = $ViewList = $FunctionList = null;
        include dirname( __DIR__ ) . '/modules/explayouts_ui_api/module.php';
        $this->assertSame( array( 'read', 'edit' ), array_keys( $FunctionList ) );
        foreach ( $ViewList as $name => $view )
            $this->assertSame( array( 'read' ), $view['functions'], $name );
    }

    public function testAnErrorShowsNoDetailsWithoutDebugOutput()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // The layouts list asks the layout service, whose stand-in throws.
        $result = expLayoutsUIApplicationApi::handle( array( 'layouts' ) );

        $this->assertSame( 500, http_response_code() );
        $this->assertSame( array( 'error' => 'Internal error' ), json_decode( $result['content'], true ) );
        $this->assertCount( 1, eZLog::$lines );
        $this->assertStringContainsString( 'Lost connection to the database server in ', eZLog::$lines[0], 'the log keeps message, file and line' );
    }

    public function testAnErrorShowsItsDetailsWithDebugOutput()
    {
        eZDebug::$enabled = true;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = expLayoutsUIApplicationApi::handle( array( 'layouts' ) );
        $body = json_decode( $result['content'], true );

        $this->assertSame( 500, http_response_code() );
        $this->assertSame( 'Internal error', $body['error'] );
        $this->assertStringContainsString( 'Lost connection to the database server', $body['details'] );
        $this->assertStringContainsString( 'bootstrap.php:', $body['details'] );
    }

    public function testTheViewPassesTheDebugStateItHadBeforeSwitchingItOff()
    {
        // Run as the module runs app/api/...: the view switches debug output off before it calls handle().
        eZDebug::$enabled = true;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/index.php/some_admin/explayouts_ui_api/app/api/layouts';
        $view = new \Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi\App();
        $result = $view->run( array( 'Params' => array() ) );
        $body = json_decode( $result['content'], true );

        $this->assertSame( 500, http_response_code() );
        $this->assertArrayHasKey( 'details', $body, 'debug output was on for the request' );
        $this->assertStringContainsString( 'Lost connection to the database server', $body['details'] );
        $this->assertFalse( eZDebug::$enabled, 'and is off for the JSON answer' );
    }

    public function testTheViewSendsNoDetailsWithoutDebugOutput()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $view = new \Exponential\View\Extension\ExplayoutsUiApi\ExplayoutsUiApi\App();
        $result = $view->run( array( 'Params' => array() ) );

        $this->assertSame( 500, http_response_code() );
        $this->assertSame( array( 'error' => 'Internal error' ), json_decode( $result['content'], true ) );
    }
}
