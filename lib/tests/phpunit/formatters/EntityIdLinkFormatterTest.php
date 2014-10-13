<?php

namespace Wikibase\Test;

use LogicException;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdLinkFormatter;

/**
 * @covers Wikibase\Lib\EntityIdLinkFormatter
 *
 * @group Wikibase
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdLinkFormatterTest extends \PHPUnit_Framework_TestCase {

	public function provideFormat() {
		return array(
			'ItemId' => array(
				new ItemId( 'Q23' ),
				'[[ITEM-TEST--Q23]]'
			),
			'PropertyId' => array(
				new PropertyId( 'P23' ),
				'[[PROPERTY-TEST--P23]]'
			),
			'EntityId' => array(
				new ItemId( 'q23' ),
				'[[ITEM-TEST--Q23]]'
			),
			'EntityIdValue' => array(
				new EntityIdValue( new ItemId( "Q23" ) ),
				'[[ITEM-TEST--Q23]]'
			),
		);
	}

	/**
	 * @dataProvider provideFormat
	 */
	public function testFormat( $id, $expected ) {
		$formatter = $this->newEntityIdLinkFormatter();

		$actual = $formatter->format( $id );
		$this->assertEquals( $expected, $actual );
	}

	public function getTitleForId( EntityId $id ) {
		if ( $id->getEntityType() === Item::ENTITY_TYPE ) {
			$name = 'ITEM-TEST--' . $id->getSerialization();
		} elseif ( $id->getEntityType() === Property::ENTITY_TYPE ) {
			$name = 'PROPERTY-TEST--' . $id->getSerialization();
		} else {
			throw new LogicException( "oops!" );
		}

		return Title::makeTitle( NS_MAIN, $name );
	}

	protected function newEntityIdLinkFormatter() {
		$options = new FormatterOptions();
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		$formatter = new EntityIdLinkFormatter( $options, $titleLookup );
		return $formatter;
	}

}
