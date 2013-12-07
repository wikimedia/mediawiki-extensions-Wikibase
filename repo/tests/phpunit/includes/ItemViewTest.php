<?php

namespace Wikibase\Test;

use Wikibase\ItemContent;
use Wikibase\Item;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Property;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Utils;
use Wikibase\ItemView;
use ValueFormatters\ValueFormatterFactory;

/**
 * @covers Wikibase\ItemView
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class ItemViewTest extends EntityViewTest {

	/**
	 * @return SnakFormatter
	 */
	protected function newSnakFormatterMock() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )->method( 'formatSnak' )
			->will( $this->returnValue( '(value)' ) );

		$snakFormatter->expects( $this->any() )->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML_WIDGET ) );

		$snakFormatter->expects( $this->any() )->method( 'canFormatSnak' )
			->will( $this->returnValue( true ) );

		return $snakFormatter;
	}

	/**
	 * @dataProvider providerNewForEntityType
	 */
	public function testNewForEntityType( $type, $expectedClass ) {
		$entityInfoBuilder = new MockRepository();
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$entityTitleLookup = $this->getEntityTitleLookupMock();

		// test whether we get the right EntityView from an EntityContent
		$view = ItemView::newForEntityType(
			$type,
			$this->newSnakFormatterMock(),
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityTitleLookup );

		$this->assertInstanceOf(
			$expectedClass,
			$view
		);
	}

	public static function providerNewForEntityType() {
		return array(
			array( Item::ENTITY_TYPE, 'Wikibase\ItemView' ),
			array( Property::ENTITY_TYPE, 'Wikibase\PropertyView' )
		);
	}

}
