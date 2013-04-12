<?php

namespace Wikibase\Tests\Query\SQLStore\SnakStore;

use DataValues\StringValue;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\SnakStore\NoValueSnakStore;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\SnakStore\NoValueSnakStore class.
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
 * @group WikibaseSnakStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NoValueSnakStoreTest extends SnakStoreTest {

	protected function getInstance() {
		return new NoValueSnakStore();
	}

	public function canStoreProvider() {
		$argLists = array();

		$argLists[] = array( false, new PropertyValueSnak( 42, new StringValue( 'nyan' ) ) );
		$argLists[] = array( false, new PropertyValueSnak( 9001, new StringValue( 'nyan' ) ) );
		$argLists[] = array( true, new PropertyNoValueSnak( 1 ) );
		$argLists[] = array( true, new PropertyNoValueSnak( 31337 ) );
		$argLists[] = array( false, new PropertySomeValueSnak( 2 ) );
		$argLists[] = array( false, new PropertySomeValueSnak( 720101 ) );

		return $argLists;
	}

}
