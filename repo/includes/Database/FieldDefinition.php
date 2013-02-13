<?php

namespace Wikibase\Repo\Database;

use InvalidArgumentException;

/**
 * Definition of a database table field.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FieldDefinition implements \Immutable {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	private $type;

	/**
	 * @since 0.4
	 *
	 * @var mixed
	 */
	private $default;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	private $attributes;

	/**
	 * @since 0.4
	 *
	 * @var boolean
	 */
	private $null;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	private $index;

	/**
	 * @since 0.4
	 *
	 * @var boolean
	 */
	private $autoIncrement;

	const TYPE_BOOLEAN = 'bool';
	const TYPE_TEXT = 'str';
	const TYPE_INTEGER = 'int';
	const TYPE_FLOAT = 'float';

	const ATTRIB_BINARY = 'binary';
	const ATTRIB_UNSIGNED = 'unsigned';


	/**
	 * @param string $name
	 * @param string $type
	 * @param mixed $default
	 * @param string|null $attributes
	 * @param boolean $null
	 * @param string $index
	 * @param boolean $autoIncrement
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $name, $type, $default = null, $attributes = null, $null = true, $index = null, $autoIncrement = false ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( 'The field $name needs to be a string' );
		}

		if ( !is_string( $type ) ) {
			throw new InvalidArgumentException( 'The field $type needs to be a string' );
		}

		if ( !is_bool( $null ) ) {
			throw new InvalidArgumentException( 'The $null parameter needs to be a boolean' );
		}

		if ( !is_string( $autoIncrement ) ) {
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

	public function getName() {
		return $this->name;
	}

	public function getType() {
		return $this->type;
	}

	public function getDefault() {
		return $this->default;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function allowsNull() {
		return $this->null;
	}

	public function getIndex() {
		return $this->index;
	}

	public function hasAutoIncrement() {
		return $this->autoIncrement;
	}

}