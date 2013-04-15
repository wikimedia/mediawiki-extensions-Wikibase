<?php

namespace Wikibase\Test\Query\SQLStore;

use DataValues\StringValue;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\StoreSnak;
use Wikibase\Snak;
use Wikibase\SnakRole;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\StoreSnak class.
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
class StoreSnakTest extends \PHPUnit_Framework_TestCase {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array(
			new PropertyNoValueSnak( 42 ),
			9001,
			31337,
			SnakRole::MAIN_SNAK
		);

		$argLists[] = array(
			new PropertySomeValueSnak( 23 ),
			9002,
			1337,
			SnakRole::QUALIFIER
		);

		$argLists[] = array(
			new PropertyValueSnak( 1, new StringValue( 'foobar baz' ) ),
			2,
			3,
			SnakRole::QUALIFIER
		);

		return $argLists;
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( Snak $snak, $internalPropertyId, $internalClaimId, $snakRole ) {
		$storeSnak = new StoreSnak( $snak, $internalPropertyId, $internalClaimId, $snakRole );
		$this->assertTrue( true );
	}

}
