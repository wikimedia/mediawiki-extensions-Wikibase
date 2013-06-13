<?php

namespace Wikibase\QueryEngine\Integration\SQLStore;

use Ask\Language\Description\Description;
use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use DataValues\NumberValue;
use DataValues\StringValue;
use NullMessageReporter;
use Wikibase\Claims;
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
use Wikibase\QueryEngine\SQLStore\DVHandler\StringHandler;
use Wikibase\QueryEngine\SQLStore\Store;
use Wikibase\QueryEngine\SQLStore\StoreConfig;
use Wikibase\Test\ClaimListAccessTest;

/**
 * Tests the write operations (those exposed by Wikibase\QueryEngine\SQLStore\Writer)
 * by verifying the entities are found only when they should be.
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
 * @group WikibaseQueryEngineIntegration
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WritingIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	protected $store;

	public function setUp() {
		if ( !defined( 'MEDIAWIKI' ) || wfGetDB( DB_MASTER )->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'Can only run DescriptionMatchFinderIntegrationTest on MySQL' );
		}

		parent::setUp();

		$this->store = $this->newStore();

		$this->store->getSetup( new NullMessageReporter() )->install();
	}

	public function tearDown() {
		if ( isset( $this->store ) ) {
			$this->store->getSetup( new NullMessageReporter() )->uninstall();
		}
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
				'string' => new StringHandler( new DataValueTable(
					new TableDefinition(
						'string',
						array(
							new FieldDefinition( 'value', FieldDefinition::TYPE_TEXT, false ),
						)
					),
					'value',
					'value',
					'value'
				) )
			)
		);

		$propertyDvTypeLookup = $this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' );

		$propertyDvTypeLookup->expects( $this->any() )
			->method( 'getDataValueTypeForProperty' )
			->will( $this->returnValue( 'string' ) );

		$config->setPropertyDataValueTypeLookup( $propertyDvTypeLookup );

		return new Store( $config, $queryInterface );
	}

	public function testInsertAndRemoveItem() {
		$item = Item::newEmpty();
		$item->setId( 8888 );

		$claim = $item->newClaim( new PropertyValueSnak( 42, new StringValue( 'Awesome' ) ) );
		$item->addClaim( $claim );

		$this->store->getUpdater()->insertEntity( $item );

		$propertyDescription = new SomeProperty(
			new EntityId( 'property', 42 ),
			new ValueDescription( new StringValue( 'Awesome' ) )
		);

		$this->assertEquals(
			array( 88880 ),
			$this->findMatchingEntities( $propertyDescription )
		);

		$this->store->getUpdater()->deleteEntity( $item );

		$this->assertEquals(
			array(),
			$this->findMatchingEntities( $propertyDescription )
		);
	}

	/**
	 * @param Description $description
	 * @return int[]
	 */
	protected function findMatchingEntities( Description $description ) {
		$matchFinder = $this->store->getDescriptionMatchFinder();

		$queryOptions = new QueryOptions(
			100,
			0
		);

		return $matchFinder->findMatchingEntities( $description, $queryOptions );
	}

	public function testUpdateItem() {
		$item = Item::newEmpty();
		$item->setId( 4444 );

		$claim = $item->newClaim( new PropertyValueSnak( 42, new StringValue( 'Awesome' ) ) );
		$item->addClaim( $claim );

		$this->store->getUpdater()->insertEntity( $item );

		$item->setClaims( new Claims( array(
			$item->newClaim( new PropertyValueSnak( 42, new StringValue( 'Foo' ) ) )
		) ) );

		$this->store->getUpdater()->updateEntity( $item );

		$propertyDescription = new SomeProperty(
			new EntityId( 'property', 42 ),
			new ValueDescription( new StringValue( 'Foo' ) )
		);

		$this->assertEquals(
			array( 44440 ),
			$this->findMatchingEntities( $propertyDescription )
		);

		$propertyDescription = new SomeProperty(
			new EntityId( 'property', 42 ),
			new ValueDescription( new StringValue( 'Awesome' ) )
		);

		$this->assertEquals(
			array(),
			$this->findMatchingEntities( $propertyDescription )
		);
	}

}
