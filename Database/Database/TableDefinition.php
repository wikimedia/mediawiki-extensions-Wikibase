<?php

namespace Wikibase\Database;

use InvalidArgumentException;

/**
 * Definition of a database table. Immutable.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableDefinition {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @since 0.1
	 *
	 * @var FieldDefinition[]
	 */
	private $fields;

	/**
	 * @since 0.1
	 *
	 * @param string $name
	 * @param FieldDefinition[] $fields
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $name, array $fields ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( 'The table $name needs to be a string' );
		}

		if ( empty( $fields ) ) {
			throw new InvalidArgumentException( 'The table $fields list cannot be empty' );
		}

		$this->name = $name;

		$this->fields = array();

		foreach ( $fields as $field ) {
			if ( !( $field instanceof FieldDefinition ) ) {
				throw new InvalidArgumentException( 'All table fields should be of type FieldDefinition' );
			}

			if ( array_key_exists( $field->getName(), $this->fields ) ) {
				throw new InvalidArgumentException( 'A table cannot have two fields with the same name' );
			}

			$this->fields[$field->getName()] = $field;
		}
	}

	/**
	 * Returns the name of the table.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the fields that make up this table.
	 * The array keys in the returned array correspond to the names
	 * of the fields defined by the value they point to.
	 *
	 * @since 0.1
	 *
	 * @return FieldDefinition[]
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Returns if the table has a field with the provided name.
	 *
	 * @since 0.1
	 *
	 * @param string $fieldName
	 *
	 * @return boolean
	 */
	public function hasFieldWithName( $fieldName ) {
		return array_key_exists( $fieldName, $this->fields );
	}

	/**
	 * Returns a clone of the table, though with the provided name instead.
	 *
	 * @since 0.1
	 *
	 * @param string $cloneName
	 *
	 * @return TableDefinition
	 */
	public function mutateName( $cloneName ) {
		return new self( $cloneName, $this->fields );
	}

	/**
	 * Returns a clone of the table, though with the provided fields rather then the original ones.
	 *
	 * @since 0.1
	 *
	 * @param FieldDefinition[] $fields
	 *
	 * @return TableDefinition
	 */
	public function mutateFields( array $fields ) {
		return new self( $this->name, $fields );
	}

	// TODO: multiple field indices

}
