<?php

namespace Wikibase\Test;
use Wikibase\ClaimList;
use Wikibase\ClaimObject;
use Wikibase\Claim;

/**
 * Tests for the Wikibase\ClaimList class.
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
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListTest extends \MediaWikiTestCase {

	public function getInstanceClass() {
		return '\Wikibase\ClaimList';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->getConstructorArg() as $arg ) {
			$instances[] = array( new $class( $arg ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		$instances = array();

		$instances[] = new \Wikibase\ClaimObject(
			new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 ) )
		);

		return $instances;
	}

	public function getConstructorArg() {
		return array(
			null,
			array(),
			$this->getElementInstances(),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testHasClaim( ClaimList $array ) {
		/**
		 * @var Claim $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasClaim( $hashable ) );
			$array->removeClaim( $hashable );
			$this->assertFalse( $array->hasClaim( $hashable ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testRemoveClaim( ClaimList $array ) {
		$elementCount = count( $array );

		/**
		 * @var Claim $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasClaim( $element ) );

			$array->removeClaim( $element );

			$this->assertFalse( $array->hasClaim( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		$array->removeClaim( $element );
		$array->removeClaim( $element );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testAddClaim( ClaimList $array ) {
		$elementCount = count( $array );

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		++$elementCount;

		$array->addClaim( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

	public function testDuplicateClaims() {
		$firstClaim = new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$secondClaim = new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$list = new ClaimList();
		$this->assertTrue( $list->addElement( $firstClaim ), 'Adding the first element should work' );
		$this->assertTrue( $list->addElement( $secondClaim ), 'Adding a duplicate element should work' );

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertTrue( $list->addElement( new ClaimObject( new \Wikibase\PropertySomeValueSnak( 1 ) ) ) );

		$list->removeDuplicates();

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Removing duplicates from a list should work' );
	}

}
