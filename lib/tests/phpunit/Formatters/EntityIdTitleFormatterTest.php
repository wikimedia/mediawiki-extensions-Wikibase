<?php

namespace Wikibase\Lib\Tests\Formatters;

use LogicException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Formatters\EntityIdTitleFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers \Wikibase\Lib\Formatters\EntityIdTitleFormatter
 *
 * @group Wikibase
 * @group ValueFormatters
 * @group DataValueExtensions
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdTitleFormatterTest extends \PHPUnit\Framework\TestCase {

	public function formatEntityIdProvider() {
		return [
			'ItemId' => [
				new ItemId( 'Q23' ),
				'ITEM-TEST--Q23',
			],
			'NumericPropertyId' => [
				new NumericPropertyId( 'P23' ),
				'PROPERTY-TEST--P23',
			],
		];
	}

	/**
	 * @dataProvider formatEntityIdProvider
	 */
	public function testFormatEntityId( EntityId $id, $expected ) {
		$formatter = $this->newEntityIdTitleFormatter();

		$actual = $formatter->formatEntityId( $id );
		$this->assertSame( $expected, $actual );
	}

	public function getTitleForId( EntityId $entityId ) {
		switch ( $entityId->getEntityType() ) {
			case Item::ENTITY_TYPE:
				return Title::makeTitle( NS_MAIN, 'ITEM-TEST--' . $entityId->getSerialization() );
			case Property::ENTITY_TYPE:
				return Title::makeTitle( NS_MAIN, 'PROPERTY-TEST--' . $entityId->getSerialization() );
			default:
				throw new LogicException( "oops!" );
		}
	}

	protected function newEntityIdTitleFormatter() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( [ $this, 'getTitleForId' ] );

		$formatter = new EntityIdTitleFormatter( $titleLookup );
		return $formatter;
	}

}
