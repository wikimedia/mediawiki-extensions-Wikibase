<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\QueryEngine\SQLStore\DataValueHandler;
use Wikibase\QueryEngine\SQLStore\DataValueHandlers;
use Wikibase\QueryEngine\SQLStore\StoreConfig;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\StoreConfig class.
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
class StoreConfigTest extends \PHPUnit_Framework_TestCase {

	public function constructorProvider() {
		$argLists = array();

		$defaultHandlers = new DataValueHandlers();

		$argLists[] = array( 'Wikibase SQL Store', 'wbsql_', array(
			'string' => $defaultHandlers->getHandler( 'string' )
		) );

		$argLists[] = array( 'SQL store with new config for migration', '', $defaultHandlers->getHandlers() );

		return $argLists;
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param string $storeName
	 * @param string $tablePrefix
	 * @param DataValueHandler[] $dvHandlers
	 */
	public function testConstructor( $storeName, $tablePrefix, $dvHandlers ) {
		$instance = new StoreConfig( $storeName, $tablePrefix, $dvHandlers );

		$this->assertEquals( $storeName, $instance->getStoreName(), 'Store name got set correctly' );
		$this->assertEquals( $dvHandlers, $instance->getDataValueHandlers(), 'DataValueHandlers got set correctly' );
		$this->assertEquals( $tablePrefix, $instance->getTablePrefix(), 'Table prefix got set correctly' );
	}

	public function testSetPropertyDataValueTypeLookup() {
		$instance = new StoreConfig( 'foo', 'bar', array() );

		$lookup = $this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' );

		$instance->setPropertyDataValueTypeLookup( $lookup );

		$this->assertEquals( $lookup, $instance->getPropertyDataValueTypeLookup() );
	}

	public function testSetPropertyDataValueTypeLookupNotSet() {
		$instance = new StoreConfig( 'foo', 'bar', array() );

		$this->setExpectedException( 'Exception' );
		$instance->getPropertyDataValueTypeLookup();
	}

}
