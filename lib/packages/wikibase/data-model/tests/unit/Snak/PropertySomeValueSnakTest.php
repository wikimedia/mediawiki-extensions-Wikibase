<?php

namespace Wikibase\Test\Snak;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertySomeValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @group Database
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
