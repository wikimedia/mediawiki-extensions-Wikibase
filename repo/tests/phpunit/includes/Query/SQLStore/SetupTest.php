<?php

namespace Wikibase\Repo\Test\Query\SQLStore;

use Wikibase\Repo\Query\SQLStore\Setup;

/**
 * Unit tests for the Wikibase\Repo\Query\SQLStore\Setup class.
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
 * @since wd.qe
 *
 * @ingroup WikibaseRepoTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetupTest extends \MediaWikiTestCase {

	/**
	 * @return \Wikibase\Repo\Database\QueryInterface
	 */
	protected function getQueryInterface() {
		$connectionProvider = new \Wikibase\Repo\LazyDBConnectionProvider( DB_MASTER );

		$queryInterface = new \Wikibase\Repo\Database\MediaWikiQueryInterface(
			$connectionProvider,
			new \Wikibase\Repo\Database\MWDB\ExtendedMySQLAbstraction( $connectionProvider )
		);

		return $queryInterface;
	}

	public function testExecutionOfRun() {
		$storeConfig = new \Wikibase\Repo\Query\SQLStore\StoreConfig( 'foo', 'wbsql_', array(
			'string' => new \Wikibase\Repo\Query\SQLStore\DVHandler\StringHandler(),
			'number' => new \Wikibase\Repo\Query\SQLStore\DVHandler\NumberHandler(),
		) );

		$queryInterface = $this->getQueryInterface();

		$storeSetup = new Setup(
			$storeConfig,
			$queryInterface,
			new \Wikibase\Repo\Database\TableBuilder( $queryInterface )
		);

		$this->assertTrue( $storeSetup->install() );

		foreach ( $storeConfig->getDataValueHandlers() as $dvHandler ) {
			foreach ( array( 'msnak_', 'qualifier_' ) as $snakLevel ) {
				$table = $dvHandler->getTableDefinition();
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
