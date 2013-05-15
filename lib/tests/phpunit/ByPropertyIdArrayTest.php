<?php

namespace Wikibase\Test;
use Wikibase\ByPropertyIdArray, Wikibase\EntityId, Wikibase\Property, Wikibase\Snak;

/**
 * Tests for the Wikibase\ByPropertyIdArray class.
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
class ByPropertyIdArrayTest extends \MediaWikiTestCase {

	public function listProvider() {
		$lists = array();

		$snaks = array(
			new \Wikibase\PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) ),
			new \Wikibase\PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) ),
			new \Wikibase\PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 10 ) ),
			new \Wikibase\PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 1 ) ),
			new \Wikibase\PropertyValueSnak( new EntityId( Property::ENTITY_TYPE, 10 ), new \DataValues\StringValue( 'ohi' ) ),
		);

		$lists[] = $snaks;

		$lists[] = array_map(
			function( \Wikibase\Snak $snak ) {
				return new \Wikibase\Claim( $snak );
			},
			$snaks
		);

		$lists[] = array_map(
			function( \Wikibase\Snak $snak ) {
				return new \Wikibase\Statement( $snak );
			},
			$snaks
		);

		return $this->arrayWrap( $lists );
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

		$this->assertArrayEquals( $expected, $indexedArray->getPropertyIds() );
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

		$this->assertArrayEquals( $objects, $allObtainedObjects );
	}

	public function testGetByNotSetIdThrowsException() {
		$indexedArray = new ByPropertyIdArray();
		$indexedArray->buildIndex();

		$this->setExpectedException( 'OutOfBoundsException' );

		$indexedArray->getByPropertyId( 9000 );
	}
}
