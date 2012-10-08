<?php

namespace Wikibase\Test;
use Wikibase\Snak;

/**
 * Unit tests for classes that implement Wikibase\Snak.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakTest extends \MediaWikiTestCase {

	public function snakProvider() {
		$snaks = array();

		$snaks[] = new \Wikibase\PropertyNoValueSnak( 1 );
		$snaks[] = new \Wikibase\PropertyNoValueSnak( 42 );

		$snaks[] = new \Wikibase\PropertySomeValueSnak( 1 );
		$snaks[] = new \Wikibase\PropertySomeValueSnak( 42 );

		$snaks[] = new \Wikibase\InstanceOfSnak( 1 );
		$snaks[] = new \Wikibase\InstanceOfSnak( 42 );

		$snaks[] = new \Wikibase\SubclassOfSnak( 1 );
		$snaks[] = new \Wikibase\SubclassOfSnak( 42 );

		$values = array();

		$values[] = new \DataValues\StringValue( 'Ohi there!' );
		$values[] = new \DataValues\NumberValue( 42 );
		$values[] = new \DataValues\QuantityValue( 4.2, 'm', 1 );

		foreach ( $values as $value ) {
			$snaks[] = new \Wikibase\PropertyValueSnak( 1, $value );
		}

		return $this->arrayWrap( $snaks );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetType( Snak $snak ) {
		$this->assertInternalType( 'string', $snak->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetPropertyId( Snak $snak ) {
		$this->assertInternalType( 'integer', $snak->getPropertyId() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testSerialize( Snak $snak ) {
		$serialization = serialize( $snak );
		$this->assertInternalType( 'string', $serialization );

		$newInstance = unserialize( $serialization );
		$this->assertInstanceOf( get_class( $snak ), $newInstance );

		$this->assertEquals( $snak, $newInstance );
		$this->assertEquals( $snak->getPropertyId(), $newInstance->getPropertyId() );
		$this->assertEquals( $snak->getType(), $newInstance->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetHash( Snak $snak ) {
		$hash = $snak->getHash();
		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( $hash, $snak->getHash() );
	}

}
