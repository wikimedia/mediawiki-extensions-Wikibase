<?php

namespace Wikibase\Test\Snak;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyNoValueSnak
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
