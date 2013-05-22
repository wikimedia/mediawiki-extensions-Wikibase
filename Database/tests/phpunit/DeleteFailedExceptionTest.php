<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\DeleteFailedException;

/**
 * @covers Wikibase\Database\DeleteFailedException
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
class DeleteFailedExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$tableName = 'nyancats';
		$conditions = array( 'foo' => 42, 'awesome > 9000' );

		$exception = new DeleteFailedException( $tableName, $conditions );

		$this->assertEquals( $tableName, $exception->getTableName() );
		$this->assertEquals( $conditions, $exception->getConditions() );
	}

	public function testConstructorWithAllArguments() {
		$tableName = 'users';
		$conditions = array( 'foo' => 42 );
		$message = 'NyanData all the way accross the sky!';
		$previous = new \Exception( 'Onoez!' );

		$exception = new DeleteFailedException( $tableName, $conditions, $message, $previous );

		$this->assertEquals( $tableName, $exception->getTableName() );
		$this->assertEquals( $conditions, $exception->getConditions() );
		$this->assertEquals( $message, $exception->getMessage() );
		$this->assertEquals( $previous, $exception->getPrevious() );
	}

}
