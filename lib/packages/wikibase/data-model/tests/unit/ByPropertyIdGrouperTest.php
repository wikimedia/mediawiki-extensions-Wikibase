<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\ByPropertyIdGrouper;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\ByPropertyIdGrouper
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ByPropertyIdGrouperTest extends \PHPUnit_Framework_TestCase {

	public function provideGetPropertyIds() {
		$cases = array();

		$cases[] = array(
			array(),
			array()
		);

		$cases[] = array(
			array(
				$this->getPropertyIdProvider( 'P42' ),
				$this->getPropertyIdProvider( 'P23' )
			),
			array(
				new PropertyId( 'P42' ),
				new PropertyId( 'P23' )
			)
		);

		$cases[] = array(
			array(
				$this->getPropertyIdProvider( 'P42' ),
				$this->getPropertyIdProvider( 'P23' ),
				$this->getPropertyIdProvider( 'P15' ),
				$this->getPropertyIdProvider( 'P42' ),
				$this->getPropertyIdProvider( 'P10' )
			),
			array(
				new PropertyId( 'P42' ),
				new PropertyId( 'P23' ),
				new PropertyId( 'P15' ),
				new PropertyId( 'P10' )
			)
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetPropertyIds
	 *
	 * @param PropertyIdProvider[] $propertyIdProviders
	 * @param PropertyId[] $expectedPropertyIds
	 */
	public function testGetPropertyIds( array $propertyIdProviders, array $expectedPropertyIds ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProviders );
		$propertyIds = $byPropertyIdGrouper->getPropertyIds();
		$this->assertEquals( $expectedPropertyIds, $propertyIds );
	}

	private function getPropertyIdProvider( $propertyId ) {
		$propertyIdProvider = $this->getMock( 'Wikibase\DataModel\PropertyIdProvider' );
		$propertyIdProvider->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( new PropertyId( $propertyId ) ) );

		return $propertyIdProvider;
	}

}
