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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class HashArrayWithoutDuplicatesTest extends HashArrayTest {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( HashArrayElement::getInstances() );
		$argLists[] = array( array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) );

		return $argLists;
	}

	public function getInstanceClass() {
		return '\Wikibase\Test\HashArrayWithoutDuplicates';
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

		if ( !$array->hasElement( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasElement( $element ), $array->addElement( $element ), 'Adding an element should only work if its not already there' );

		$this->assertEquals( $elementCount, $array->count(), 'Element count should only increase if the element was not there yet' );

		$this->assertFalse( $array->addElement( $element ), 'Adding an already present element should not work' );

		$this->assertEquals( $elementCount, $array->count(), 'Element count should not increase if the element is already there' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testRemoveDuplicates( HashArray $array ) {
		$count = count( $array );
		$array->removeDuplicates();

		$this->assertEquals( $count, count( $array ), 'Count should be the same after removeDuplicates since there can be none' );
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

		$hasElement = $array->hasElement( $element );
		$array->addElement( $element );

		$this->assertTrue( ( $hash === $array->getHash() ) === $hasElement );
	}

}

class HashArrayWithoutDuplicates extends HashArray {

	public function getObjectType() {
		return '\Hashable';
	}

	public function __construct( $input = null ) {
		$this->acceptDuplicates = false;
		parent::__construct( $input );
	}

}


