<?php
/**
 * The JSON API of the layouts editor: with the form token switched on, every request that changes something (any
 * method but GET, HEAD and OPTIONS) needs the token, in the X-CSRF-Token header or the ezxform_token field. The token
 * is checked before the policy, so a request without it is refused even for a user who may edit, and reaches no layout.
 *
 * Each test runs in its own process: handle() sends headers and a status code.
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
class FormTokenTest extends TestCase
{
    const TOKEN = 'a1b2c3d4e5f6';

    protected function setUp(): void
    {
        eZUser::$functions = array( 'explayouts_ui_api/read', 'explayouts_ui_api/edit' );
        eZUser::$asked = array();
        eZDebug::$enabled = false;
        eZLog::$lines = array();
        foreach ( array( 'expLayoutsLayout', 'expLayoutsZone', 'expLayoutsLayoutType', 'expLayoutsCollection', 'expLayoutsBlock' ) as $standIn )
            $standIn::reset();
        eZDB::$tables = array();
        eZDB::$statements = array();
        ezxFormToken::$enabled = true;
        ezxFormToken::$token = self::TOKEN;
        $_POST = array();
        unset( $_SERVER['HTTP_X_CSRF_TOKEN'] );
        $_SERVER['REQUEST_URI'] = '/explayouts_ui_api/app/api/transfer/import';
    }

    public static function changingMethods(): array
    {
        return array( 'POST' => array( 'POST' ), 'PATCH' => array( 'PATCH' ), 'PUT' => array( 'PUT' ), 'DELETE' => array( 'DELETE' ) );
    }

    protected static function reason( array $result ): string
    {
        $body = json_decode( $result['content'], true );
        return (string)$body['error']['reason'];
    }

    /**
     * @dataProvider changingMethods
     */
    #[DataProvider( 'changingMethods' )]
    public function testAChangeWithoutTheTokenIsRefusedBeforeTheLayouts( string $method )
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = array( 'identifier' => 'imported', 'name' => 'Imported', 'layout_type' => '1_column', 'zones' => array() );
        $result = expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        $this->assertSame( 403, http_response_code() );
        $this->assertSame( 'form_token_missing', self::reason( $result ) );
        $this->assertSame( array(), expLayoutsLayout::$calls, 'nothing was touched' );
        $this->assertSame( array(), eZUser::$asked, 'the token is checked before the policy' );
    }

    /**
     * @dataProvider changingMethods
     */
    #[DataProvider( 'changingMethods' )]
    public function testAChangeWithAWrongTokenIsRefused( string $method )
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'not-the-token';
        $result = expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        $this->assertSame( 403, http_response_code() );
        $this->assertSame( 'form_token_wrong', self::reason( $result ) );
        $this->assertSame( array(), expLayoutsLayout::$calls, 'nothing was touched' );
    }

    public function testAPostWithTheTokenInTheHeaderGoesOn()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = self::TOKEN;
        $_POST = array( 'identifier' => 'imported', 'name' => 'Imported', 'layout_type' => '1_column', 'zones' => array() );
        expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        // the import checks the layout type against the settings first: the request got as far as the layouts
        $this->assertSame( array( 'getTypeInfo' ), expLayoutsLayoutType::$calls );
    }

    public function testAPostWithTheTokenInTheFormFieldGoesOn()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array( 'identifier' => 'imported', 'name' => 'Imported', 'layout_type' => '1_column', 'zones' => array(),
                        ezxFormToken::FORM_FIELD => self::TOKEN );
        expLayoutsUIApplicationApi::handle( array( 'transfer', 'import' ) );

        // the import checks the layout type against the settings first: the request got as far as the layouts
        $this->assertSame( array( 'getTypeInfo' ), expLayoutsLayoutType::$calls );
    }

    public function testAReadNeedsNoToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = expLayoutsUIApplicationApi::handle( array( 'share', '7' ) );

        $this->assertNotSame( 'form_token_missing', json_decode( (string)$result['content'], true )['error']['reason'] ?? '' );
    }
}
