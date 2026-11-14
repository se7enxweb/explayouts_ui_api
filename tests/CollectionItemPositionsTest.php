<?php
/**
 * Items added to or moved in a collection get positions of their own. "+ Add items" sends every chosen item at the
 * collection's offset, last first; each was stored at exactly that position, so two items chosen together ended up
 * at position 0 next to the item that was there.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Opens the protected helper of the dispatcher to the test. */
class expLayoutsUIApplicationApiPositionProbe extends expLayoutsUIApplicationApi
{
    public static function place( $collection, $item, $position ) { self::placeCollectionItem( $collection, $item, $position ); }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
#[RunTestsInSeparateProcesses]
class CollectionItemPositionsTest extends TestCase
{
    private static function items( array $positions ): array
    {
        $items = array();
        foreach ( $positions as $id => $position )
            $items[] = array( 'id' => $id, 'position' => $position );
        return $items;
    }

    /** id => position, ordered by position (the order the collection shows). */
    private static function ordered( array $positions ): array
    {
        asort( $positions );
        return $positions;
    }

    public function testTwoItemsAddedAtTheOffsetComeFirstInTheOrderChosen(): void
    {
        // Items 1, 2, 3 at 0, 1, 2. The editor chose A (10) then B (11) and sends B first, then A, both at 0.
        $positions = array( 1 => 0, 2 => 1, 3 => 2, 11 => 3 );
        $positions = expLayoutsUIApplicationApi::collectionItemPositions( self::items( $positions ), 11, 0, false );
        $positions[10] = 4;
        $positions = expLayoutsUIApplicationApi::collectionItemPositions( self::items( $positions ), 10, 0, false );

        $this->assertSame( array( 10 => 0, 11 => 1, 1 => 2, 2 => 3, 3 => 4 ), self::ordered( $positions ) );
    }

    public function testAManualCollectionHasNoGapsAndNoSharedPositions(): void
    {
        // Positions as an earlier version stored them: 0, 0, 0, 1, 2 and a gap.
        $positions = expLayoutsUIApplicationApi::collectionItemPositions(
            self::items( array( 1 => 0, 5 => 0, 6 => 0, 2 => 1, 3 => 7 ) ), 9, 2, false );

        $this->assertSame( array( 1 => 0, 5 => 1, 9 => 2, 6 => 3, 2 => 4, 3 => 5 ), self::ordered( $positions ) );
    }

    public function testAPositionPastTheEndOrNoneAppends(): void
    {
        $items = self::items( array( 1 => 0, 2 => 1 ) );
        $this->assertSame( array( 1 => 0, 2 => 1, 9 => 2 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 9, 40, false ) ) );
        $this->assertSame( array( 1 => 0, 2 => 1, 9 => 2 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 9, null, false ) ) );
        $this->assertSame( array( 9 => 0, 1 => 1, 2 => 2 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 9, -3, false ) ) );
    }

    public function testMovingAnItemShiftsTheItemsInBetween(): void
    {
        $items = self::items( array( 1 => 0, 2 => 1, 3 => 2, 4 => 3 ) );
        $this->assertSame( array( 2 => 0, 3 => 1, 1 => 2, 4 => 3 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 1, 2, false ) ) );
        $this->assertSame( array( 4 => 0, 1 => 1, 2 => 2, 3 => 3 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 4, 0, false ) ) );
    }

    public function testADynamicCollectionKeepsItsPinnedSlots(): void
    {
        // Pinned at slots 0, 1 and 5: a new item at 0 pushes the run 0, 1 down to 1, 2; slot 5 stays.
        $items = self::items( array( 1 => 0, 2 => 1, 3 => 5, 9 => 6 ) );
        $this->assertSame( array( 9 => 0, 1 => 1, 2 => 2, 3 => 5 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 9, 0, true ) ) );
        // A free slot is taken as it is.
        $this->assertSame( array( 1 => 0, 2 => 1, 9 => 3, 3 => 5 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions( $items, 9, 3, true ) ) );
        // Two pinned items in one slot (stored by an earlier version) get slots of their own.
        $this->assertSame( array( 1 => 0, 2 => 1, 9 => 2, 3 => 5 ), self::ordered( expLayoutsUIApplicationApi::collectionItemPositions(
            self::items( array( 1 => 0, 2 => 0, 3 => 5 ) ), 9, 2, true ) ) );
    }

    public function testPlacingStoresOnlyTheItemsThatMove(): void
    {
        expLayoutsCollectionItem::reset();
        $stored = array(
            new expLayoutsCollectionItem( array( 'id' => 1, 'position' => 0 ) ),
            new expLayoutsCollectionItem( array( 'id' => 2, 'position' => 1 ) ),
            new expLayoutsCollectionItem( array( 'id' => 3, 'position' => 2 ) ),
        );
        $new = new expLayoutsCollectionItem( array( 'id' => 4, 'position' => 3 ) );
        expLayoutsCollectionItem::$returns['fetchByCollection'] = array_merge( $stored, array( $new ) );
        $collection = new expLayoutsCollection( array( 'id' => 7, 'collection_type' => 'manual' ) );

        expLayoutsUIApplicationApiPositionProbe::place( $collection, $new, 1 );

        $this->assertSame( array( 0, 2, 3 ), array_map( function ( $i ) { return $i->attribute( 'position' ); }, $stored ) );
        $this->assertSame( 1, $new->attribute( 'position' ) );
        $this->assertSame( array( 0, 1, 1, 1 ), array( $stored[0]->stored, $stored[1]->stored, $stored[2]->stored, $new->stored ) );
    }
}
