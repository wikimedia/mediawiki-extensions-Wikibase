<?php

namespace Wikibase\DataModel\Tests\Snak;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyNoValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PropertyNoValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		return array(
			array( true, new PropertyId( 'P1' ) ),
			array( true, new PropertyId( 'P9001' ) ),
		);
	}

	public function getClass() {
		return 'Wikibase\DataModel\Snak\PropertyNoValueSnak';
	}

	public function testEquals_givenOtherSnakImplementation_isNotEqual() {
		$propertyId = new PropertyId( 'P1' );
		$noValue = new PropertyNoValueSnak( $propertyId );
		$someValue = new PropertySomeValueSnak( $propertyId );

		$this->assertFalse( $noValue->equals( $someValue ) );
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P1' ) );
		$hash = $snak->getHash();

		$expected = sha1( 'C:43:"Wikibase\DataModel\Snak\PropertyNoValueSnak":4:{i:1;}' );
		$this->assertSame( $expected, $hash );
	}

}
