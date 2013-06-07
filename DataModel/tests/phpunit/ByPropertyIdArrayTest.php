<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\ByPropertyIdArray;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Snak;
use Wikibase\Claim;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Statement;

/**
 * @covers Wikibase\ByPropertyIdArray
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
 * @group ByPropertyIdArrayTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyIdArrayTest extends \PHPUnit_Framework_TestCase {

	public function listProvider() {
		$lists = array();

		$snaks = array(
			new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) ),
			new PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) ),
			new PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 10 ) ),
			new PropertyValueSnak( new EntityId( Property::ENTITY_TYPE, 10 ), new StringValue( 'ohi' ) ),
			new PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 1 ) ),
		);

		$lists[] = $snaks;

		$lists[] = array_map(
			function( Snak $snak ) {
				return new Claim( $snak );
			},
			$snaks
		);

		$lists[] = array_map(
			function( Snak $snak ) {
				return new Statement( $snak );
			},
			$snaks
		);

		$argLists = array();

		foreach ( $lists as $list ) {
			$argLists[] = array( $list );
		}

		return $argLists;
	}

	/**
	 * @dataProvider listProvider
	 * @param Snak[] $objects
	 */
	public function testGetIds( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );

		$expected = array();

		foreach ( $objects as $object ) {
			$expected[] = $object->getPropertyId()->getNumericId();
		}

		$expected = array_unique( $expected );

		$indexedArray->buildIndex();

		$this->assertEquals(
			array_values( $expected ),
			array_values( $indexedArray->getPropertyIds() )
		);
	}

	/**
	 * @dataProvider listProvider
	 * @param array $objects
	 */
	public function testGetById( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );

		$ids = array();

		foreach ( $objects as $object ) {
			$ids[] = $object->getPropertyId()->getNumericId();
		}

		$ids = array_unique( $ids );

		$indexedArray->buildIndex();

		$allObtainedObjects = array();

		foreach ( $ids as $id ) {
			foreach ( $indexedArray->getByPropertyId( $id ) as $obtainedObject ) {
				$allObtainedObjects[] = $obtainedObject;
				$this->assertEquals( $id, $obtainedObject->getPropertyId()->getNumericId() );
			}
		}

		$this->assertEquals(
			array_values( $objects ),
			array_values( $allObtainedObjects )
		);
	}

	public function testGetByNotSetIdThrowsException() {
		$indexedArray = new ByPropertyIdArray();
		$indexedArray->buildIndex();

		$this->setExpectedException( 'OutOfBoundsException' );

		$indexedArray->getByPropertyId( 9000 );
	}

	public function testNotBuildExceptionIsThrownForByPropertyId() {
		$indexedArray = new ByPropertyIdArray();

		$this->setExpectedException( 'RuntimeException' );
		$indexedArray->getByPropertyId( 9000 );
	}

	public function testNotBuildExceptionIsThrownForGetPropertyIds() {
		$indexedArray = new ByPropertyIdArray();

		$this->setExpectedException( 'RuntimeException' );
		$indexedArray->getPropertyIds();
	}

}
