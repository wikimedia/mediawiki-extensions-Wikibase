<?php

namespace Wikibase\Database;

use InvalidArgumentException;

/**
 * Definition of a database table field. Immutable.
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
class FieldDefinition {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	private $type;

	/**
	 * @since 0.1
	 *
	 * @var mixed
	 */
	private $default;

	/**
	 * @since 0.1
	 *
	 * @var string|null
	 */
	private $attributes;

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	private $null;

	/**
	 * @since 0.1
	 *
	 * @var string|null
	 */
	private $index;

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	private $autoIncrement;

	const TYPE_BOOLEAN = 'bool';
	const TYPE_TEXT = 'str';
	const TYPE_INTEGER = 'int';
	const TYPE_FLOAT = 'float';

	const NOT_NULL = false;
	const NULL = true;

	const NO_DEFAULT = null;

	const NO_ATTRIB = null;
	const ATTRIB_BINARY = 'binary';
	const ATTRIB_UNSIGNED = 'unsigned';

	const NO_INDEX = null;
	const INDEX = 'index';
	const INDEX_UNIQUE = 'unique';
	const INDEX_FULLTEXT = 'fulltext';
	const INDEX_PRIMARY = 'primary';

	const AUTOINCREMENT = true;
	const NO_AUTOINCREMENT = false;


	/**
	 * @since 0.1
	 *
	 * @param string $name
	 * @param string $type
	 * @param boolean $null
	 * @param mixed $default
	 * @param string|null $attributes
	 * @param string|null $index
	 * @param boolean $autoIncrement
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $name, $type, $null = self::NULL, $default = self::NO_DEFAULT, $attributes = null, $index = null, $autoIncrement = false ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( 'The field $name needs to be a string' );
		}

		if ( !is_string( $type ) ) {
			throw new InvalidArgumentException( 'The field $type needs to be a string' );
		}

		if ( !is_bool( $null ) ) {
			throw new InvalidArgumentException( 'The $null parameter needs to be a boolean' );
		}

		if ( !is_null( $index ) && !is_string( $index ) ) {
			throw new InvalidArgumentException( 'The $index parameter needs to be a string' );
		}

		if ( !is_bool( $autoIncrement ) ) {
			throw new InvalidArgumentException( 'The $autoIncrement parameter needs to be a boolean' );
		}

		$this->name = $name;
		$this->type = $type;
		$this->default = $default;
		$this->attributes = $attributes;
		$this->null = $null;
		$this->index = $index;
		$this->autoIncrement = $autoIncrement;
	}

	/**
	 * Returns the name of the field.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the type of the field.
	 * This is one of the TYPE_ constants.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns the default value of the field.
	 * Null for no default value.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * Returns the attributes of the field.
	 * This is one of the ATTRIB_ constants or null.
	 *
	 * @since 0.1
	 *
	 * @return string|null
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Returns if the field allows for the value to be null.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function allowsNull() {
		return $this->null;
	}

	/**
	 * Returns the index type of the field.
	 * This is one of the INDEX_ constants or null.
	 *
	 * @since 0.1
	 *
	 * @return string|null
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Returns if the field has auto increment.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasAutoIncrement() {
		return $this->autoIncrement;
	}

}
