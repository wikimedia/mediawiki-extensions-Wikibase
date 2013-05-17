<?php

namespace Wikibase\QueryEngine\Integration\SQLStore\Engine;

use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\LazyDBConnectionProvider;
use Wikibase\Database\MediaWikiQueryInterface;
use Wikibase\Database\MWDB\ExtendedMySQLAbstraction;
use Wikibase\Database\TableBuilder;
use Wikibase\Database\TableDefinition;
use Wikibase\EntityId;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\DVHandler\NumberHandler;
use Wikibase\QueryEngine\SQLStore\Engine\DescriptionMatchFinder;
use Wikibase\QueryEngine\SQLStore\EntityIdTransformer;
use Wikibase\QueryEngine\SQLStore\Schema;
use Wikibase\QueryEngine\SQLStore\Setup;
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
	 * @var DescriptionMatchFinder
	 */
	protected $matchFinder;

	/**
	 * @var Setup
	 */
	protected $setup;

	public function setUp() {
		parent::setUp();

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
						'number',
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

		$schema = new Schema( $config );

		$setup = new Setup( $config, $schema, $queryInterface, new TableBuilder( $queryInterface ) );
		$setup->install();



		$this->setup = $setup;

		$dvTypeLookup = $this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' );

		$dvTypeLookup->expects( $this->any() )
			->method( 'getDataValueTypeForProperty' )
			->will( $this->returnValue( 'number' ) );

		$matchFinder = new DescriptionMatchFinder(
			$queryInterface,
			$schema,
			$dvTypeLookup,
			new EntityIdTransformer( array( 'property' => 0 ) )
		);

		$this->matchFinder = $matchFinder;
	}

	public function tearDown() {
		$this->setup->uninstall();
	}

	public function testFindMatchingEntities() {
		$matchFinder = $this->matchFinder;

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

	}


}
