<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\FieldDefinition;

/**
 * @covers Wikibase\Database\FieldDefinition
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
class FieldDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function instanceProvider() {
		$instances = array();

		$instances[] = new FieldDefinition(
			'names',
			FieldDefinition::TYPE_TEXT
		);

		$instances[] = new FieldDefinition(
			'numbers',
			FieldDefinition::TYPE_FLOAT
		);

		$instances[] = new FieldDefinition(
			'stuffs',
			FieldDefinition::TYPE_INTEGER,
			false,
			42,
			FieldDefinition::ATTRIB_UNSIGNED
		);

		$instances[] = new FieldDefinition(
			'stuffs',
			FieldDefinition::TYPE_INTEGER,
			true,
			null,
			null
		);

		$instances[] = new FieldDefinition(
			'stuffs',
			FieldDefinition::TYPE_INTEGER,
			true,
			null,
			null,
			null,
			true
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
	 * @param FieldDefinition $field
	 */
	public function testReturnValueOfGetName( FieldDefinition $field ) {
		$this->assertInternalType( 'string', $field->getName() );

		$newField = new FieldDefinition( $field->getName(), $field->getType() );

		$this->assertEquals(
			$field->getName(),
			$newField->getName(),
			'The FieldDefinition name is set and obtained correctly'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param FieldDefinition $field
	 */
	public function testReturnValueOfGetType( FieldDefinition $field ) {
		$this->assertInternalType( 'string', $field->getType() );

		$newField = new FieldDefinition( $field->getName(), $field->getType() );

		$this->assertEquals(
			$field->getType(),
			$newField->getType(),
			'The FieldDefinition type is set and obtained correctly'
		);
	}

}
