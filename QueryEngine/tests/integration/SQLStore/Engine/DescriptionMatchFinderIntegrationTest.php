<?php

namespace Wikibase\QueryEngine\Integration\SQLStore\Engine;

use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use DataValues\NumberValue;
use NullMessageReporter;
use Wikibase\Claim;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\LazyDBConnectionProvider;
use Wikibase\Database\MediaWikiQueryInterface;
use Wikibase\Database\MWDB\ExtendedMySQLAbstraction;
use Wikibase\Database\TableDefinition;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\DVHandler\NumberHandler;
use Wikibase\QueryEngine\SQLStore\Store;
use Wikibase\QueryEngine\SQLStore\StoreConfig;

/**
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
class DescriptionMatchFinderIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	protected $store;

	public function setUp() {
		parent::setUp();

		$this->store = $this->newStore();

		$this->store->getSetup( new NullMessageReporter() )->install();

		$this->insertEntities();
	}

	protected function newStore() {
		$dbConnectionProvider = new LazyDBConnectionProvider( DB_MASTER );

		$queryInterface = new MediaWikiQueryInterface(
			$dbConnectionProvider,
			new ExtendedMySQLAbstraction( $dbConnectionProvider )
		);

		$config = new StoreConfig(
			'test_store',
			'integrationtest_',
			array(
				'number' => new NumberHandler( new DataValueTable(
					new TableDefinition(
						'number_table',
						array(
							new FieldDefinition( 'value', FieldDefinition::TYPE_FLOAT, false ),
							new FieldDefinition( 'json', FieldDefinition::TYPE_TEXT, false ),
						)
					),
					'json',
					'value',
					'value'
				) )
			)
		);

		$propertyDvTypeLookup = $this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' );

		$propertyDvTypeLookup->expects( $this->any() )
			->method( 'getDataValueTypeForProperty' )
			->will( $this->returnValue( 'number' ) );

		$config->setPropertyDataValueTypeLookup( $propertyDvTypeLookup );

		return new Store( $config, $queryInterface );
	}

	protected function insertEntities() {
		$item = Item::newEmpty();
		$item->setId( 1732 );

		$claim = $item->newClaim( new PropertyValueSnak( 42, new NumberValue( 1337 ) ) );
		$item->addClaim( $claim );

		$this->store->getUpdater()->insertEntity( $item );
	}

	public function tearDown() {
		$this->store->getSetup( new NullMessageReporter() )->uninstall();
	}

	public function testFindMatchingEntities() {
		$matchFinder = $this->store->getDescriptionMatchFinder();

		$description = new SomeProperty(
			new EntityId( 'property', 42 ),
			new ValueDescription( new NumberValue( 1337 ) )
		);

		$queryOptions = new QueryOptions(
			100,
			0
		);

		$matchingEntityIds = $matchFinder->findMatchingEntities( $description, $queryOptions );

		$this->assertInternalType( 'array', $matchingEntityIds );
		$this->assertContainsOnly( 'int', $matchingEntityIds );

		$this->assertEquals( array( 1732 ), $matchingEntityIds );

	}


}
