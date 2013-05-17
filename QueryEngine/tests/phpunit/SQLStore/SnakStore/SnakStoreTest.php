<?php

namespace Wikibase\QueryEngine\Tests\SQLStore\SnakStore;

use DataValues\StringValue;
use Wikibase\QueryEngine\SQLStore\Schema;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRow;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakRow;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakRow;
use Wikibase\QueryEngine\SQLStore\StoreConfig;
use Wikibase\SnakRole;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore implementing classes.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakStoreTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SnakStore
	 */
	protected abstract function getInstance();

	protected abstract function canStoreProvider();

	protected abstract function cannotStoreProvider();

	public function differentSnaksProvider() {
		$argLists = array();

		$argLists[] = array( new ValuelessSnakRow(
			ValuelessSnakRow::TYPE_NO_VALUE,
			1,
			2,
			SnakRole::QUALIFIER,
			3
		) );

		$argLists[] = array( new ValuelessSnakRow(
			ValuelessSnakRow::TYPE_SOME_VALUE,
			3,
			4,
			SnakRole::MAIN_SNAK,
			5
		) );

		$argLists[] = array( new ValueSnakRow(
			new StringValue( '~=[,,_,,]:3' ),
			42,
			31337,
			SnakRole::MAIN_SNAK,
			9001
		) );

		return $argLists;
	}

	/**
	 * @dataProvider differentSnaksProvider
	 */
	public function testReturnTypeOfCanUse( SnakRow $snak ) {
		$canStore = $this->getInstance()->canStore( $snak );
		$this->assertInternalType( 'boolean', $canStore );
	}

	/**
	 * @dataProvider canStoreProvider
	 */
	public function testCanStore( SnakRow $snak ) {
		$this->assertTrue( $this->getInstance()->canStore( $snak ) );
	}

	/**
	 * @dataProvider cannotStoreProvider
	 */
	public function testCannotStore( SnakRow $snak ) {
		$this->assertFalse( $this->getInstance()->canStore( $snak ) );
	}

	protected function newStoreSchema() {
		return new Schema( new StoreConfig( 'foobar', 'nyan_', array() ) );
	}

	/**
	 * @dataProvider cannotStoreProvider
	 */
	public function testStoreSnakWithWrongSnakType( SnakRow $snakRow ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->getInstance()->storeSnakRow( $snakRow );
	}

}
