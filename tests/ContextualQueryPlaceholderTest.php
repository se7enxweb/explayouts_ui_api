<?php
/**
 * A block whose collection is a query on the page the layout is shown on (use_current_location, or the topic of the
 * current content) finds nothing in the editor, which shows no page. As the reference editor, the canvas then draws
 * placeholder rows, not "No content" (expLayoutsUIApplicationApi::contextualQueryPlaceholders(), contextualPlaceholderHtml()).
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\TestCase;

if ( !class_exists( 'expLayoutsCollectionQuery', false ) )
    eval( 'class expLayoutsCollectionQuery { use expLayoutsTestStandIn; }' );

class ContextualQueryPlaceholderTest extends TestCase
{
    protected function given( $type, ?array $parameters = null, $limit = 0 )
    {
        expLayoutsCollection::reset();
        expLayoutsCollectionQuery::reset();
        expLayoutsCollection::$returns['fetchByBlock'] = new expLayoutsCollection( array( 'id' => 3, 'collection_type' => $type, 'limit_value' => $limit ) );
        expLayoutsCollectionQuery::$returns['fetchByCollection'] = $parameters === null ? false
            : new expLayoutsCollectionQuery( array( 'parameters' => json_encode( $parameters ) ) );
    }

    public function testAQueryOnTheCurrentPageGetsPlaceholderRows()
    {
        $this->given( 'dynamic', array( 'use_current_location' => true, 'content_types' => array( 'image' ) ) );
        $this->assertSame( 12, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'no limit: 12, as the reference' );
        $this->given( 'dynamic', array( 'use_current_location' => true ), 4 );
        $this->assertSame( 4, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'the collection\'s limit' );
        $this->given( 'dynamic', array( 'use_topic_from_current_content' => true ), 30 );
        $this->assertSame( 12, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'the topic of the current content, at most 12' );
    }

    public function testAnyOtherBlockGetsNone()
    {
        $this->given( 'dynamic', array( 'use_current_location' => false, 'parent_location_id' => 2 ) );
        $this->assertSame( 0, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'a query on a fixed location' );
        $this->given( 'manual' );
        $this->assertSame( 0, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'a manual collection' );
        $this->given( 'dynamic' );
        $this->assertSame( 0, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 7 ), 'no query' );
        $this->assertSame( 0, expLayoutsUIApplicationApi::contextualQueryPlaceholders( 0 ), 'no block' );
    }

    public function testThePlaceholderRows()
    {
        $html = expLayoutsUIApplicationApi::contextualPlaceholderHtml( 3 );
        $this->assertSame( 3, substr_count( $html, '<div class="contextual-placeholder-row">' ) );
        $this->assertStringContainsString( 'aria-label="Items of the page this layout is shown on"', $html );
        $this->assertStringNotContainsString( 'No content', $html );
    }
}
