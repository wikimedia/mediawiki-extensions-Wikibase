<?php

namespace Wikibase\Test\Database;

use Wikibase\Database\ResultIterator;

/**
 * @covers Wikibase\Database\TableBuilder
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
 * @ingroup WikibaseDatabaseTest
 *
 * @group Wikibase
 * @group WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		new ResultIterator( array() );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider rowProvider
	 */
	public function testRetainsInputData( array $inputRows ) {
		$iterator = new ResultIterator( $inputRows );

		$this->assertEquals(
			$inputRows,
			iterator_to_array( $iterator )
		);
	}

	public function rowProvider() {
		$argLists = array();

		$argLists[] = array( array(
		) );

		$argLists[] = array( array(
			(object)array( 'foo' => 4, 'bar' => 2 ),
		) );

		$argLists[] = array( array(
			(object)array( 'foo' => 4, 'bar' => 2 ),
			(object)array( 'foo' => 1, 'bar' => 3 ),
		) );

		$argLists[] = array( array(
			(object)array( 'foo' => 4, 'bar' => 2 ),
			(object)array( 'foo' => 1, 'bar' => 3 ),
			(object)array( 'baz' => 'nyan', 'bah' => 'cat' ),
		) );

		return $argLists;
	}

}
