<?php

namespace Wikibase\Test;
use Wikibase\HashArray;
use Hashable;

/**
 * Tests for the Wikibase\HashArray class.
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
 * @group HashArrayWithDuplicatesTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class HashArrayWithDuplicatesTest extends HashArrayTest {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( HashArrayElement::getInstances() );
		$argLists[] = array( array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) );

		return $argLists;
	}

	public function getInstanceClass() {
		return '\Wikibase\Test\HashArrayWithDuplicates';
	}

	public function elementInstancesProvider() {
		return $this->arrayWrap( array_merge(
			$this->arrayWrap( HashArrayElement::getInstances() ),
			array( HashArrayElement::getInstances() )
		) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testAddElement( HashArray $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		++$elementCount;

		$this->assertTrue( $array->addElement( $element ), 'Adding an element should always work' );

		$this->assertEquals( $elementCount, $array->count(), 'Adding an element should always increase the count' );

		$this->assertTrue( $array->addElement( $element ), 'Adding an element should always work' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testRemoveDuplicates( HashArray $array ) {
		$count = count( $array );
		$duplicateCount = 0;
		$hashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( $array as $hashable ) {
			if ( in_array( $hashable->getHash(), $hashes ) ) {
				$duplicateCount++;
			}
			else {
				$hashes[] = $hashable->getHash();
			}
		}

		$array->removeDuplicates();

		$this->assertEquals( $count - $duplicateCount, count( $array ), 'Count should decrease by the number of duplicates after removing duplicates' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testGetHash( HashArray $array ) {
		$hash = $array->getHash();

		$this->assertEquals( $hash, $array->getHash() );

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		$array->addElement( $element );

		$newHash = $array->getHash();

		$this->assertFalse( $hash === $newHash, 'Hash should not be the same after adding an element' );

		$array->addElement( $element );

		$this->assertFalse( $newHash === $array->getHash(), 'Hash should not be the same after adding an existing element again' );
	}

}

class HashArrayWithDuplicates extends HashArray {

	public function getObjectType() {
		return '\Hashable';
	}

	public function __construct( $input = null ) {
		$this->acceptDuplicates = true;
		parent::__construct( $input );
	}

}