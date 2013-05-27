<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\Database\TableBuilder;
use Wikibase\QueryEngine\SQLStore\DataValueHandlers;
use Wikibase\QueryEngine\SQLStore\Schema;
use Wikibase\QueryEngine\SQLStore\Setup;
use Wikibase\QueryEngine\SQLStore\StoreConfig;

/**
 * @covers Wikibase\QueryEngine\SQLStore\Setup
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
class SetupTest extends \PHPUnit_Framework_TestCase {

	public function testInstall() {
		$defaultHandlers = new DataValueHandlers();
		$storeConfig = new StoreConfig( 'foo', 'wbsql_', $defaultHandlers->getHandlers() );
		$schema = new Schema( $storeConfig );
		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryInterface->expects( $this->atLeastOnce() )
			->method( 'createTable' )
			->will( $this->returnValue( true ) );

		$storeSetup = new Setup(
			$storeConfig,
			$schema,
			$queryInterface,
			new TableBuilder( $queryInterface )
		);

		$storeSetup->install();
	}

	public function testUninstall() {
		$defaultHandlers = new DataValueHandlers();
		$storeConfig = new StoreConfig( 'foo', 'wbsql_', $defaultHandlers->getHandlers() );
		$schema = new Schema( $storeConfig );
		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryInterface->expects( $this->atLeastOnce() )
			->method( 'dropTable' )
			->will( $this->returnValue( true ) );

		$storeSetup = new Setup(
			$storeConfig,
			$schema,
			$queryInterface,
			new TableBuilder( $queryInterface )
		);

		$storeSetup->uninstall();
	}

}
