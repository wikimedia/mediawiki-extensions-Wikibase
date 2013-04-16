<?php

namespace Wikibase\Tests\QueryEngine\SQLStore;

use DataValues\StringValue;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\EntityIdMap;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakInserter;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRowBuilder;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore;
use Wikibase\Snak;
use Wikibase\SnakRole;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\SnakInserter class.
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
class SnakInserterTest extends \PHPUnit_Framework_TestCase {

	public function snakProvider() {
		$argLists = array();

		$argLists[] = array( new PropertyNoValueSnak( 1 ) );

		$argLists[] = array( new PropertyNoValueSnak( 31337 ) );

		$argLists[] = array( new PropertySomeValueSnak( 3 ) );

		$argLists[] = array( new PropertyValueSnak( 4, new StringValue( 'NyanData' ) ) );

		return $argLists;
	}

	/**
	 * @dataProvider snakProvider
	 */
	public function testInsertSnak( Snak $snak ) {
		$snakInserter = $this->newInstance();

		// TODO: asserts
		//$snakInserter->insertSnak( $snak, SnakRole::MAIN_SNAK, 9001 );
	}

	/**
	 * @param int $snakRole
	 *
	 * @return SnakStore[]
	 */
	protected function getSnakStores( $snakRole ) {
		return array(
			new ValuelessSnakStore(
				$this->getMock( 'Wikibase\Database\QueryInterface' ),
				'test_table'
			),
			new ValueSnakStore(
				$this->getMock( 'Wikibase\Database\QueryInterface' ),
				array()
			)
		);
	}

	protected function newInstance() {
		return new SnakInserter(
			$this->getSnakStores( SnakRole::MAIN_SNAK ),
			new SnakRowBuilder( new EntityIdMap() )
		);
	}

}
