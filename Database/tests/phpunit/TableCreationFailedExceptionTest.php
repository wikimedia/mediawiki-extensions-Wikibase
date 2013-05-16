<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\TableCreationFailedException;

/**
 * @covers Wikibase\Database\TableCreationFailedException class.
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
class TableCreationFailedExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustATable() {
		$table = $this->getMockBuilder( 'Wikibase\Database\TableDefinition' )
			->disableOriginalConstructor()->getMock();

		$exception = new TableCreationFailedException( $table );

		$this->assertEquals( $table, $exception->getTable() );
	}

	public function testConstructorWithAllArguments() {
		$table = $this->getMockBuilder( 'Wikibase\Database\TableDefinition' )
			->disableOriginalConstructor()->getMock();

		$message = 'NyanData all the way accross the sky!';

		$previous = new \Exception( 'Onoez!' );

		$exception = new TableCreationFailedException( $table, $message, $previous );

		$this->assertEquals( $table, $exception->getTable() );
		$this->assertEquals( $message, $exception->getMessage() );
		$this->assertEquals( $previous, $exception->getPrevious() );
	}

}
