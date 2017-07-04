<?php

namespace Wikibase\Lib\Tests\Formatters;

use LogicException;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdTitleFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers Wikibase\Lib\EntityIdTitleFormatter
 *
 * @group Wikibase
 * @group ValueFormatters
 * @group DataValueExtensions
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityIdTitleFormatterTest extends PHPUnit_Framework_TestCase {

	public function formatEntityIdProvider() {
		return [
			'ItemId' => [
				new ItemId( 'Q23' ),
				'ITEM-TEST--Q23'
			],
			'PropertyId' => [
				new PropertyId( 'P23' ),
				'PROPERTY-TEST--P23'
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
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( [ $this, 'getTitleForId' ] ) );

		$formatter = new EntityIdTitleFormatter( $titleLookup );
		return $formatter;
	}

}
