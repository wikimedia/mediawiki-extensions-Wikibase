<?php

namespace Wikibase\Test\Database;

use Wikibase\Database\TableBuilder;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\Database\ObservableQueryInterface;
use NullMessageReporter;

/**
 * Unit tests for the Wikibase\Database\TableBuilder class.
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
 * @since wd.db
 *
 * @ingroup WikibaseDatabaseTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableBuilderTest extends \MediaWikiTestCase {

	public function tableNameProvider() {
		return $this->arrayWrap(
			array(
				'foo',
				'bar',
				'o',
				'foo_bar_baz',
				'foobarbaz',
			)
		);
	}

	/**
	 * @dataProvider tableNameProvider
	 */
	public function testCreateTableCallsTableExists( $tableName ) {
		$table = new TableDefinition(
			$tableName,
			array( new FieldDefinition( 'foo', FieldDefinition::TYPE_TEXT ) )
		);

		$reporter = new NullMessageReporter();

		$queryInterface = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryInterface->expects( $this->once() )
			->method( 'tableExists' )
			->with( $table->getName() );

		$builder = new TableBuilder( $queryInterface, $reporter );

		$builder->createTable( $table );
	}

}
