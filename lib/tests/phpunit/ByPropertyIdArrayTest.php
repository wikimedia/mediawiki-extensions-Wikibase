<?php

namespace Wikibase\Test;
use Wikibase\ByPropertyIdArray;

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
 * @ingroup Wikibase
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
			new \Wikibase\PropertyNoValueSnak( 42 ),
			new \Wikibase\PropertySomeValueSnak( 42 ),
			new \Wikibase\PropertySomeValueSnak( 10 ),
			new \Wikibase\PropertySomeValueSnak( 1 ),
			new \Wikibase\PropertyValueSnak( 10, new \DataValues\StringValue( 'ohi' ) ),
		);

		$lists[] = $snaks;

		$lists[] = array_map(
			function( \Wikibase\Snak $snak ) {
				return new \Wikibase\ClaimObject( $snak );
			},
			$snaks
		);

		$lists[] = array_map(
			function( \Wikibase\Snak $snak ) {
				return new \Wikibase\StatementObject( $snak );
			},
			$snaks
		);

		return $this->arrayWrap( $lists );
	}

	/**
	 * @dataProvider listProvider
	 * @param array $objects
	 */
	public function testGetIds( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );

		$expected = array();

		foreach ( $objects as $object ) {
			$expected[] = $object->getPropertyId();
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
			$ids[] = $object->getPropertyId();
		}

		$ids = array_unique( $ids );

		$indexedArray->buildIndex();

		$allObtainedObjects = array();

		foreach ( $ids as $id ) {
			foreach ( $indexedArray->getByPropertyId( $id ) as $obtainedObject ) {
				$allObtainedObjects[] = $obtainedObject;
				$this->assertEquals( $id, $obtainedObject->getPropertyId() );
			}
		}

		$this->assertArrayEquals( $objects, $allObtainedObjects );
	}

}
