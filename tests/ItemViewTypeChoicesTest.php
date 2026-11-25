<?php
/**
 * The item view type select of the Design tab (expLayoutsUIApplicationApi::itemViewTypeChoices()): every view type
 * offers every item view type, except a view type the settings limit (explayouts.ini ViewTypeItemViewTypes[]), as
 * Netgen Layouts lists item_view_types per view type. netgen-layouts.js shows the options whose data-master names the
 * selected view type and hides the select where one is left.
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\TestCase;

class ItemViewTypeChoicesTest extends TestCase
{
    const VIEW_TYPES = array( 'carousel', 'sushi_bar', 'gallery' );
    const ITEM_VIEW_TYPES = array( 'carousel' => 'Carousel', 'gallery_grid' => 'Gallery grid', 'overlay' => 'Overlay', 'line' => 'Line' );
    const LIMITS = array( 'carousel' => array( 'carousel' ), 'sushi_bar' => array( 'overlay', 'line' ) );

    /** value => the view types that offer it */
    protected static function masters( array $choices )
    {
        $masters = array();
        foreach ( $choices['options'] as $option )
            $masters[$option['value']] = $option['masters'];
        return $masters;
    }

    public function testWithoutLimitsEveryViewTypeOffersEveryItemViewType()
    {
        $choices = expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, array(), 'carousel', 'gallery_grid' );
        $all = array( '' => self::VIEW_TYPES, 'carousel' => self::VIEW_TYPES, 'gallery_grid' => self::VIEW_TYPES,
                      'overlay' => self::VIEW_TYPES, 'line' => self::VIEW_TYPES );
        $this->assertSame( $all, self::masters( $choices ) );
        $this->assertSame( 'gallery_grid', $choices['selected'] );
    }

    public function testALimitedViewTypeOffersOnlyItsOwnAndNoDefault()
    {
        $choices = expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'carousel', 'carousel' );
        $this->assertSame( array(
            '' => array( 'gallery' ),
            'carousel' => array( 'carousel', 'gallery' ),
            'gallery_grid' => array( 'gallery' ),
            'overlay' => array( 'sushi_bar', 'gallery' ),
            'line' => array( 'sushi_bar', 'gallery' ),
        ), self::masters( $choices ) );
        $this->assertSame( 'carousel', $choices['selected'] );
    }

    public function testAStoredItemViewTheViewTypeDoesNotListGivesWayToItsFirst()
    {
        // a carousel that kept gallery_grid from an earlier view type
        $this->assertSame( 'carousel', expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'carousel', 'gallery_grid' )['selected'] );
        $this->assertSame( 'overlay', expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'sushi_bar', '' )['selected'] );
        $this->assertSame( 'line', expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'sushi_bar', 'line' )['selected'] );
        // an unlimited view type keeps whatever is stored, '' among it
        $this->assertSame( '', expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'gallery', '' )['selected'] );
    }

    public function testAStoredItemViewTheDefinitionDoesNotKnowStaysWhereItIsAllowed()
    {
        $choices = expLayoutsUIApplicationApi::itemViewTypeChoices( self::VIEW_TYPES, self::ITEM_VIEW_TYPES, self::LIMITS, 'gallery', 'polaroid' );
        $this->assertSame( 'polaroid', $choices['selected'] );
        $last = end( $choices['options'] );
        $this->assertSame( array( 'value' => 'polaroid', 'label' => false, 'masters' => array( 'gallery' ) ), $last );
        // on a limited view type it is replaced, and not offered
        $choices = expLayoutsUIApplicationApi::itemViewTypeChoices( array( 'carousel' ), self::ITEM_VIEW_TYPES, self::LIMITS, 'carousel', 'polaroid' );
        $this->assertSame( 'carousel', $choices['selected'] );
        $this->assertSame( array( 'carousel' => array( 'carousel' ) ), self::masters( $choices ), 'only the carousel item view, no default' );
    }

    public function testAListedItemViewTheDefinitionDoesNotNameIsOfferedByItsIdentifier()
    {
        $choices = expLayoutsUIApplicationApi::itemViewTypeChoices( array( 'grid_tiles' ), array( 'standard' => 'Standard' ), array( 'grid_tiles' => array( 'tile' ) ), 'grid_tiles', '' );
        $this->assertSame( array( array( 'value' => 'tile', 'label' => 'tile', 'masters' => array( 'grid_tiles' ) ) ), $choices['options'] );
        $this->assertSame( 'tile', $choices['selected'] );
    }
}
