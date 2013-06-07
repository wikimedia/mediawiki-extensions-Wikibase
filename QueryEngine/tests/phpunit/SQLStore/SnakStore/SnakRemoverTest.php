<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRemover;

/**
 * @covers Wikibase\QueryEngine\SQLStore\SnakStore\SnakRemover
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
class SnakRemoverTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider subjectIdProvider
	 */
	public function testRemoveSnaksOfSubject( $internalSubjectId ) {
		$valuelessStore = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore' )
			->disableOriginalConstructor()->getMock();
		$valueStore = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore' )
			->disableOriginalConstructor()->getMock();

		$valuelessStore->expects( $this->once() )
			->method( 'removeSnaksOfSubject' )
			->with( $this->equalTo( $internalSubjectId ) );

		$valueStore->expects( $this->once() )
			->method( 'removeSnaksOfSubject' )
			->with( $this->equalTo( $internalSubjectId ) );

		$snakRemover = new SnakRemover( array( $valuelessStore, $valueStore ) );

		$snakRemover->removeSnaksOfSubject( $internalSubjectId );
	}

	public function subjectIdProvider() {
		$argLists = array();

		$argLists[] = array( 1 );
		$argLists[] = array( 10 );
		$argLists[] = array( 11 );
		$argLists[] = array( 4242 );

		return $argLists;
	}

}
