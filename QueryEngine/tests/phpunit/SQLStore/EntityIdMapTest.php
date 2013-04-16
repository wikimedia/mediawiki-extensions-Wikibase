<?php

namespace Wikibase\Test\Query\SQLStore;

use Wikibase\QueryEngine\SQLStore\EntityIdMap;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\EntityIdMap class.
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
 * @ingroup WikibaseQueryEngineTest
 *
 * @group Wikibase
 * @group WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdMapTest extends \PHPUnit_Framework_TestCase {

	public function idProvider() {
		$argLists = array();

		$argLists[] = array( 'item', 1, 1 );
		$argLists[] = array( 'item', 4, 2 );
		$argLists[] = array( 'item', 9001, 31337 );
		$argLists[] = array( 'property', 42, 23 );
		$argLists[] = array( 'foobar', 500, 7201010 );

		return $argLists;
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testAddAndGetId( $entityType, $entityNumber, $internalId ) {
		$idMap = new EntityIdMap();

		$idMap->addId( $entityType, $entityNumber, $internalId );

		$obtainedId = $idMap->getInternalIdForEntity( $entityType, $entityNumber );

		$this->assertEquals( $internalId, $obtainedId );
	}

	public function testAddOverrides() {
		$idMap = new EntityIdMap();

		$idMap->addId( 'foo', 1, 42 );
		$idMap->addId( 'foo', 1, 1337 );

		$this->assertEquals( 1337, $idMap->getInternalIdForEntity( 'foo', 1 ) );
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGetNonSet( $entityType, $entityNumber, $internalId ) {
		$idMap = new EntityIdMap();

		$idMap->addId( 'foo', 1, 42 );
		$idMap->addId( 'bar', 2, 1337 );
		$idMap->addId( 'baz', 3, $internalId );
		$idMap->addId( 'baz', $entityNumber, $internalId );
		$idMap->addId( $entityType, 0, $internalId );

		$this->setExpectedException( 'OutOfBoundsException' );

		$idMap->getInternalIdForEntity( $entityType, $entityNumber );
	}

}
