<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use DataValues\StringValue;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\QueryInterface;
use Wikibase\Database\TableDefinition;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\DVHandler\StringHandler;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakInserter;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRowBuilder;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore;
use Wikibase\Snak;
use Wikibase\SnakRole;

/**
 * @covers Wikibase\QueryEngine\SQLStore\SnakInserter
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
		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryInterface
			->expects( $this->once() )
			->method( 'insert' )
			->with( $this->equalTo( 'test_table' ) );

		$snakInserter = $this->newInstance( $queryInterface );

		$snakInserter->insertSnak( $snak, SnakRole::MAIN_SNAK, 9001, 123 );
	}

	protected function newInstance( QueryInterface $queryInterface ) {
		$idFinder = $this->getMock( 'Wikibase\QueryEngine\SQLStore\InternalEntityIdFinder' );
		$idFinder->expects( $this->any() )
			->method( 'getInternalIdForEntity' )
			->will( $this->returnValue( 42 ) );

		return new SnakInserter(
			$this->getSnakStores( $queryInterface ),
			new SnakRowBuilder( $idFinder )
		);
	}

	protected function getSnakStores( QueryInterface $queryInterface ) {
		return array(
			new ValuelessSnakStore(
				$queryInterface,
				'test_table'
			),
			new ValueSnakStore(
				$queryInterface,
				array(
					'string' => $this->newStringHandler()
				),
				SnakRole::MAIN_SNAK
			)
		);
	}

	protected function newStringHandler() {
		return new StringHandler( new DataValueTable(
			new TableDefinition(
				'test_table',
				array(
					new FieldDefinition( 'value', FieldDefinition::TYPE_TEXT, false ),
				)
			),
			'value',
			'value',
			'value'
		) );
	}

}
