<?php

namespace Wikibase\Tests\Query\SQLStore\SnakStore;

use DataValues\StringValue;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore;
use Wikibase\Snak;

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

	public function differentSnaksProvider() {
		$argLists = array();

		$argLists[] = array( new PropertyNoValueSnak( 42 ) );
		$argLists[] = array( new PropertySomeValueSnak( 42 ) );
		$argLists[] = array( new PropertyValueSnak( 42, new StringValue( '~=[,,_,,]:3' ) ) );

		$argLists[] = array( new PropertyNoValueSnak( 31337 ) );
		$argLists[] = array( new PropertySomeValueSnak( 31337 ) );
		$argLists[] = array( new PropertyValueSnak( 31337, new StringValue( '~=[,,_,,]:3' ) ) );

		return $argLists;
	}

	/**
	 * @dataProvider differentSnaksProvider
	 * @param Snak $snak
	 */
	public function testReturnTypeOfCanUse( Snak $snak ) {
		$canStore = $this->getInstance()->canStore( $snak );
		$this->assertInternalType( 'boolean', $canStore );
	}

	/**
	 * @dataProvider canStoreProvider
	 */
	public function testCanStore( $canStore, Snak $snak ) {
		$this->assertEquals( $canStore, $this->getInstance()->canStore( $snak ) );
	}

}
