<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;

/**
 * @covers Wikibase\Database\TableDefinition
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
class TableDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function instanceProvider() {
		$instances = array();

		$instances[] = new TableDefinition(
			'snaks',
			array(
				new FieldDefinition( 'omnomnom', FieldDefinition::TYPE_TEXT )
			)
		);

		$instances[] = new TableDefinition(
			'spam',
			array(
				new FieldDefinition( 'o', FieldDefinition::TYPE_TEXT ),
				new FieldDefinition( 'h', FieldDefinition::TYPE_TEXT ),
				new FieldDefinition( 'i', FieldDefinition::TYPE_INTEGER, false, 42 ),
			)
		);

		$argLists = array();

		foreach ( $instances as $instance ) {
			$argLists[] = array( $instance );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testReturnValueOfGetName( TableDefinition $table ) {
		$this->assertInternalType( 'string', $table->getName() );

		$newTable = new TableDefinition( $table->getName(), $table->getFields() );

		$this->assertEquals(
			$table->getName(),
			$newTable->getName(),
			'The TableDefinition name is set and obtained correctly'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testReturnValueOfGetFields( TableDefinition $table ) {
		$this->assertInternalType( 'array', $table->getFields() );
		$this->assertContainsOnlyInstancesOf( 'Wikibase\Database\FieldDefinition', $table->getFields() );

		foreach ( $table->getFields() as $expectedName => $field ) {
			$this->assertEquals(
				$expectedName,
				$field->getName(),
				'The array key matches the corresponding field name'
			);
		}

		$newTable = new TableDefinition( $table->getName(), $table->getFields() );

		$this->assertEquals(
			$table->getFields(),
			$newTable->getFields(),
			'The TableDefinition fields are set and obtained correctly'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testReturnValueOfHasField( TableDefinition $table ) {
		foreach ( $table->getFields() as $field ) {
			$this->assertTrue( $table->hasFieldWithName( $field->getName() ) );
		}

		$this->assertFalse( $table->hasFieldWithName( 'zsfrcvbxuyiyrewrbmndsrbtfocszdf' ) );
		$this->assertFalse( $table->hasFieldWithName( '' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testMutateName( TableDefinition $table ) {
		$newTable = $table->mutateName( $table->getName() );

		$this->assertInstanceOf( get_class( $table ), $newTable );
		$this->assertEquals( $table, $newTable );

		$newTable = $table->mutateName( 'foobarbaz' );

		$this->assertEquals( 'foobarbaz', $newTable->getName() );
		$this->assertEquals( $table->getFields(), $newTable->getFields() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testMutateFields( TableDefinition $table ) {
		$newTable = $table->mutateFields( $table->getFields() );

		$this->assertInstanceOf( get_class( $table ), $newTable );
		$this->assertEquals( $table, $newTable );

		$fields = array(
			new FieldDefinition( 'h', FieldDefinition::TYPE_TEXT ),
			new FieldDefinition( 'a', FieldDefinition::TYPE_BOOLEAN ),
			new FieldDefinition( 'x', FieldDefinition::TYPE_INTEGER ),
		);

		$newTable = $table->mutateFields( $fields );

		$this->assertEquals( $fields, array_values( $newTable->getFields() ) );
		$this->assertEquals( $table->getName(), $newTable->getName() );
	}

}
