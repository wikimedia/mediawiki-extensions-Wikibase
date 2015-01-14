<?php

namespace Wikibase\DataModel\Tests\Snak;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertySomeValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PropertySomeValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		return array(
			array( true, new PropertyId( 'P1' ) ),
			array( true, new PropertyId( 'P9001' ) ),
		);
	}

	public function getClass() {
		return 'Wikibase\DataModel\Snak\PropertySomeValueSnak';
	}

	public function testEquals_givenOtherSnakImplementation_isNotEqual() {
		$propertyId = new PropertyId( 'P1' );
		$someValue = new PropertySomeValueSnak( $propertyId );
		$noValue = new PropertyNoValueSnak( $propertyId );

		$this->assertFalse( $someValue->equals( $noValue ) );
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$hash = $snak->getHash();

		$expected = sha1( 'C:45:"Wikibase\DataModel\Snak\PropertySomeValueSnak":4:{i:1;}' );
		$this->assertSame( $expected, $hash );
	}

}
