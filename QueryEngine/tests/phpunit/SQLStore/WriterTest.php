<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\QueryEngine\SQLStore\DVHandler\BooleanHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\MonolingualTextHandler;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\Schema;
use Wikibase\QueryEngine\SQLStore\StoreConfig;
use Wikibase\QueryEngine\SQLStore\Writer;
use Wikibase\QueryEngine\Tests\QueryStoreUpdaterTest;

/**
 * @covers Wikibase\QueryEngine\SQLStore\Writer
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
class WriterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testInsertEntityWithoutClaims( Entity $entity ) {
		$entityInserter = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityInserter' )
			->disableOriginalConstructor()->getMock();

		$entityUpdater = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityUpdater' )
			->disableOriginalConstructor()->getMock();

		$entityRemover = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityRemover' )
			->disableOriginalConstructor()->getMock();

		$writer = new Writer( $entityInserter, $entityUpdater, $entityRemover );

		$entityInserter->expects( $this->once() )->method( 'insertEntity' )->with( $this->equalTo( $entity ) );

		$writer->insertEntity( $entity );

		$entityUpdater->expects( $this->once() )->method( 'updateEntity' )->with( $this->equalTo( $entity ) );

		$writer->updateEntity( $entity );

		$entityRemover->expects( $this->once() )->method( 'removeEntity' )->with( $this->equalTo( $entity ) );

		$writer->deleteEntity( $entity );
	}

	protected function getInstance() {

	}

	public function entityProvider() {
		$argLists = array();

		$item = Item::newEmpty();
		$item->setId( 42 );

		$argLists[] = array( $item );


		$item = Item::newEmpty();
		$item->setId( 31337 );

		$argLists[] = array( $item );


		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$property->setId( 9001 );

		$argLists[] = array( $property );

		return $argLists;
	}

}
