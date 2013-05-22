<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\EntityId;
use Wikibase\QueryEngine\SQLStore\SimpleEntityIdTransformer;

/**
 * @covers Wikibase\QueryEngine\SQLStore\EntityIdTransformer
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
 * @author Denny Vrandecic
 */
class SimpleEntityIdTransformerTest extends \PHPUnit_Framework_TestCase {

	public function testConstruct() {
		new SimpleEntityIdTransformer( $this->getIdMap() );
		$this->assertTrue( true );
	}

	protected function getIdMap() {
		return array(
			'item' => 0,
			'property' => 1,
			'query' => 2,
			'foobar' => 9,
		);
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGetInternalIdForEntity( $entityType, $numericId ) {
		$idMap = $this->getIdMap();

		$transformer = new SimpleEntityIdTransformer( $idMap );

		$internalId = $transformer->getInternalIdForEntity( new EntityId( $entityType, $numericId ) );

		$this->assertInternalType( 'int', $internalId );

		$this->assertEquals(
			$numericId,
			floor( $internalId / 10 ),
			'Internal id divided by 10 should result in the numeric id'
		);

		$this->assertEquals(
			$idMap[$entityType],
			$internalId % 10,
			'The last diget of the internal id should be the number for the entity type'
		);
	}

	public function idProvider() {
		$argLists = array();

		$argLists[] = array( 'item', 1 );
		$argLists[] = array( 'item', 4 );
		$argLists[] = array( 'item', 9001 );
		$argLists[] = array( 'property', 42 );
		$argLists[] = array( 'foobar', 500 );

		return $argLists;
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGetForNotSetType( $entityType, $numericId ) {
		$this->setExpectedException( 'OutOfBoundsException' );

		$transformer = new SimpleEntityIdTransformer( array() );

		$transformer->getInternalIdForEntity( new EntityId( $entityType, $numericId ) );
	}

}
