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
class WriterTest extends QueryStoreUpdaterTest {

	/**
	 * @see QueryStoreUpdaterTest::getInstances
	 *
	 * @since 0.1
	 *
	 * @return Writer[]
	 */
	protected function getInstances() {
		$instances = array();

		$entityInserter = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityInserter' )
			->disableOriginalConstructor()->getMock();

		$instances[] = new Writer( $entityInserter );

		return $instances;
	}

	protected function newStoreSchema() {
		$dataValueHandlers = array();

		$dataValueHandlers['boolean'] = new BooleanHandler( new DataValueTable(
			new TableDefinition(
				'boolean',
				array(
					new FieldDefinition( 'value', FieldDefinition::TYPE_BOOLEAN, false ),
				)
			),
			'value',
			'value'
		) );

		$dataValueHandlers['monolingualtext'] = new MonolingualTextHandler( new DataValueTable(
			new TableDefinition(
				'mono_text',
				array(
					new FieldDefinition( 'text', FieldDefinition::TYPE_TEXT, false ),
					new FieldDefinition( 'language', FieldDefinition::TYPE_TEXT, false ),
					new FieldDefinition( 'json', FieldDefinition::TYPE_TEXT, false ),
				)
			),
			'json',
			'text',
			'text'
		) );

		return new Schema( new StoreConfig( 'foobar', 'nyan_', $dataValueHandlers ) );
	}

	public function entityWithoutClaimsProvider() {
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

	/**
	 * @dataProvider entityWithoutClaimsProvider
	 */
	public function testInsertEntityWithoutClaims( Entity $entity ) {
		$entityInserter = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\EntityInserter' )
			->disableOriginalConstructor()->getMock();

		$entityInserter->expects( $this->once() )
			->method( 'insertEntity' )
			->with( $this->equalTo( $entity ) );

		$updater = new Writer( $entityInserter );

		$updater->insertEntity( $entity );
	}

}
