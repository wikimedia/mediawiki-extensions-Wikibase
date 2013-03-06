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
	 * @since wd.qe
	 *
	 * @return Setup[]
	 */
	protected function getInstances() {
		$instances = array();

		$connectionProvider = new \Wikibase\Repo\LazyDBConnectionProvider( DB_MASTER );
		$storeConfig = new \Wikibase\Repo\Query\SQLStore\StoreConfig( 'foo', 'bar', array() );
		$queryInterface = new \Wikibase\Repo\Database\MediaWikiQueryInterface(
			$connectionProvider,
			new \Wikibase\Repo\Database\MWDB\ExtendedMySQLAbstraction( $connectionProvider )
		);

		$instances[] = new Setup(
			$storeConfig,
			new \Wikibase\Repo\Database\TableBuilder( $queryInterface )
		);

		return $instances;
	}

	/**
	 * @since wd.qe
	 *
	 * @return Setup[][]
	 */
	public function instanceProvider() {
		return $this->arrayWrap( $this->getInstances() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Setup $storeSetup
	 */
	public function testExecutionOfRun( Setup $storeSetup ) {
		$storeSetup->run();

		$this->assertTrue( true );
	}

	// TODO: add more detailed tests

}
