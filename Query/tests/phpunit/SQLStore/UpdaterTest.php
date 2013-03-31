<?php

namespace Wikibase\Test\Query\SQLStore;

use Wikibase\Database\MWDB\ExtendedMySQLAbstraction;
use Wikibase\Database\MediaWikiQueryInterface;
use Wikibase\Repo\LazyDBConnectionProvider;
use Wikibase\Query\SQLStore\Schema;
use Wikibase\Query\SQLStore\StoreConfig;
use Wikibase\Query\SQLStore\Updater;
use Wikibase\Test\Query\QueryStoreUpdaterTest;

/**
 * Unit tests for the Wikibase\Query\SQLStore\Updater class.
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
class UpdaterTest extends QueryStoreUpdaterTest {

	/**
	 * @see QueryStoreUpdaterTest::getInstances
	 *
	 * @since 0.1
	 *
	 * @return Updater[]
	 */
	protected function getInstances() {
		$instances = array();

		$connectionProvider = new LazyDBConnectionProvider( DB_MASTER );
		$storeSchema = new Schema( new StoreConfig( 'foo', 'bar', array() ) );
		$queryInterface = new MediaWikiQueryInterface(
			$connectionProvider,
			new ExtendedMySQLAbstraction( $connectionProvider )
		);

		$instances[] = new Updater( $storeSchema, $queryInterface );

		return $instances;
	}

	public function testFoo() {
		// TODO
		$this->assertTrue( true );
	}

}
