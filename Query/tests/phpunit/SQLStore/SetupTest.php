<?php

namespace Wikibase\Test\Query\SQLStore;

use Wikibase\Database\MWDB\ExtendedMySQLAbstraction;
use Wikibase\Database\MediaWikiQueryInterface;
use Wikibase\Database\TableBuilder;
use Wikibase\Repo\LazyDBConnectionProvider;
use Wikibase\Query\SQLStore\DataValueHandlers;
use Wikibase\Query\SQLStore\Schema;
use Wikibase\Query\SQLStore\Setup;
use Wikibase\Query\SQLStore\StoreConfig;

/**
 * Unit tests for the Wikibase\Query\SQLStore\Setup class.
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
 * @ingroup WikibaseQueryTest
 *
 * @group Wikibase
 * @group WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetupTest extends \MediaWikiTestCase {

	/**
	 * @return \Wikibase\Database\QueryInterface
	 */
	protected function getQueryInterface() {
		$connectionProvider = new LazyDBConnectionProvider( DB_MASTER );

		$queryInterface = new MediaWikiQueryInterface(
			$connectionProvider,
			new ExtendedMySQLAbstraction( $connectionProvider )
		);

		return $queryInterface;
	}

	public function testExecutionOfRun() {
		$defaultHandlers = new DataValueHandlers();

		$storeConfig = new StoreConfig( 'foo', 'wbsql_', $defaultHandlers->getHandlers() );

		$schema = new Schema( $storeConfig );

		$queryInterface = $this->getQueryInterface();

		$storeSetup = new Setup(
			$storeConfig,
			$schema,
			$queryInterface,
			new TableBuilder( $queryInterface )
		);

		$this->assertTrue( $storeSetup->install() );

		foreach ( $storeConfig->getDataValueHandlers() as $dvHandler ) {
			foreach ( array( 'mainsnak_', 'qualifier_' ) as $snakLevel ) {
				$table = $dvHandler->getDataValueTable()->getTableDefinition();
				$tableName = $storeConfig->getTablePrefix() . $snakLevel . $table->getName();

				$this->assertTrue(
					$queryInterface->tableExists( $tableName ),
					'Table "' . $tableName . '" should exist after store setup'
				);
			}
		}

		$this->assertTrue( $storeSetup->uninstall() );
	}

	// TODO: add more detailed tests

}
