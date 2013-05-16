<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\TableBuilder;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;

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
class TableBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider tableNameProvider
	 */
	public function testCreateTableCallsTableExists( $tableName ) {
		$table = new TableDefinition(
			$tableName,
			array( new FieldDefinition( 'foo', FieldDefinition::TYPE_TEXT ) )
		);

		$reporter = $this->getMock( 'Wikibase\Database\MessageReporter' );

		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryInterface->expects( $this->once() )
			->method( 'tableExists' )
			->with( $table->getName() );

		$builder = new TableBuilder( $queryInterface, $reporter );

		$builder->createTable( $table );
	}

	public function tableNameProvider() {
		return array(
			array( 'foo' ),
			array( 'bar' ),
			array( 'o' ),
			array( 'foo_bar_baz' ),
			array( 'foobarbaz ' ),
		);
	}

}
