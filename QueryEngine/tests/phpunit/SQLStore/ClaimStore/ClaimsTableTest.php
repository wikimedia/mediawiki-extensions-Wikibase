<?php

namespace Wikibase\QueryEngine\Tests\SQLStore\ClaimStore;

use Wikibase\Database\QueryInterface;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimRow;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable;
use Wikibase\Statement;

/**
 * @covers  Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable
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
class ClaimsTableTest extends \PHPUnit_Framework_TestCase {

	protected function getInstance( QueryInterface $queryInterface ) {
		return new ClaimsTable( $queryInterface, 'test_claims' );
	}

	public function claimRowProvider() {
		$argLists = array();

		$argLists[] = array( new ClaimRow(
			null,
			'foo-bar-guid',
			2,
			3,
			Statement::RANK_NORMAL,
			sha1( 'NyanData' )
		) );

		$argLists[] = array( new ClaimRow(
			null,
			'foo-bar-baz-guid',
			31337,
			7201010,
			Statement::RANK_PREFERRED,
			sha1( 'danweeds' )
		) );

		return $argLists;
	}

	/**
	 * @dataProvider claimRowProvider
	 */
	public function testInsertClaimRow( ClaimRow $claimRow ) {
		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );
		$queryInterface->expects( $this->once() )
			->method( 'getInsertId' )
			->will( $this->returnValue( 42 ) );

		$table = $this->getInstance( $queryInterface );

		$queryInterface->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( 'test_claims' )
			);

		$insertionId = $table->insertClaimRow( $claimRow );
		$this->assertInternalType( 'int', $insertionId );
		$this->assertEquals( 42, $insertionId );
	}

	public function testInsertRowWithId() {
		$claimRow = new ClaimRow(
			42,
			'foo-bar-baz-guid',
			31337,
			7201010,
			Statement::RANK_PREFERRED,
			sha1( 'danweeds' )
		);

		$table = $this->getInstance( $this->getMock( 'Wikibase\Database\QueryInterface' ) );

		$this->setExpectedException( 'InvalidArgumentException' );
		$table->insertClaimRow( $claimRow );
	}

}
