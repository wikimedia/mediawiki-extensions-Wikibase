<?php

namespace Wikibase\Database\Tests\MWDB;

use Wikibase\Database\MWDB\ExtendedAbstraction;
use Wikibase\Database\TableDefinition;
use Wikibase\Database\FieldDefinition;

/**
 * Base class with tests for the Wikibase\Database\MWDB\ExtendedAbstraction deriving classes.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ExtendedAbstractionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ExtendedAbstraction
	 */
	protected abstract function newInstance();

	protected function tearDown() {
		parent::tearDown();

		$this->dropTablesIfStillThere();
	}

	protected function dropTablesIfStillThere() {
		$queryInterface = $this->newInstance();

		foreach ( array( 'differentfieldtypes', 'defaultfieldvalues', 'notnullfields' ) as $tableName ) {
			if ( $queryInterface->getDB()->tableExists( $tableName ) ) {
				$queryInterface->getDB()->dropTable( $tableName );
			}
		}
	}

	public function tableProvider() {
		$tables = array();

		$tables[] = new TableDefinition( 'differentfieldtypes', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER ),
			new FieldDefinition( 'floatfield', FieldDefinition::TYPE_FLOAT ),
			new FieldDefinition( 'textfield', FieldDefinition::TYPE_TEXT ),
			new FieldDefinition( 'boolfield', FieldDefinition::TYPE_BOOLEAN ),
		) );

		$tables[] = new TableDefinition( 'defaultfieldvalues', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER, true, 42 ),
		) );

		$tables[] = new TableDefinition( 'notnullfields', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER, false ),
			new FieldDefinition( 'textfield', FieldDefinition::TYPE_TEXT, false ),
		) );

		$argLists = array();

		foreach ( $tables as $table ) {
			$argLists[] = array( $table );
		}

		return $argLists;
	}

	/**
	 * @dataProvider tableProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testCreateAndDropTable( TableDefinition $table ) {
		$extendedAbstraction = $this->newInstance();

		$this->assertFalse(
			$extendedAbstraction->getDB()->tableExists( $table->getName() ),
			'Table should not exist before creation'
		);

		$success = $extendedAbstraction->createTable( $table );

		$this->assertTrue(
			$success,
			'Creation function returned success'
		);

		$this->assertTrue(
			$extendedAbstraction->getDB()->tableExists( $table->getName() ),
			'Table "' . $table->getName() . '" exists after creation'
		);

		$this->assertTrue(
			$extendedAbstraction->getDB()->dropTable( $table->getName() ),
			'Table removal worked'
		);

		$this->assertFalse(
			$extendedAbstraction->getDB()->tableExists( $table->getName() ),
			'Table should not exist after deletion'
		);
	}

}
