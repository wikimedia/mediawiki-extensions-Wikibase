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

	public function testFacadeForwardsCalls() {
		$entityInserter = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityInserter' )
			->disableOriginalConstructor()->getMock();

		$entityUpdater = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityUpdater' )
			->disableOriginalConstructor()->getMock();

		$entityRemover = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityRemover' )
			->disableOriginalConstructor()->getMock();

		$writer = new Writer( $entityInserter, $entityUpdater, $entityRemover );

		$entityRemover->expects( $this->exactly( 1 ) )->method( 'removeEntity' );
		$entityUpdater->expects( $this->exactly( 2 ) )->method( 'updateEntity' );
		$entityInserter->expects( $this->exactly( 3 ) )->method( 'insertEntity' );

		$writer->deleteEntity( Item::newEmpty() );

		$writer->updateEntity( Item::newEmpty() );
		$writer->updateEntity( Item::newEmpty() );

		$writer->insertEntity( Item::newEmpty() );
		$writer->insertEntity( Item::newEmpty() );
		$writer->insertEntity( Item::newEmpty() );
	}

}
