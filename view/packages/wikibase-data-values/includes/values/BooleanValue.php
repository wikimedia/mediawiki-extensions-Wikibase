<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Class representing a boolean value.
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
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BooleanValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	protected $value;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $value ) {
		if ( !is_bool( $value ) ) {
			throw new InvalidArgumentException( 'Can only construct BooleanValue from booleans' );
		}

		$this->value = $value;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->value ? '1' : '0';
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return BooleanValue
	 */
	public function unserialize( $value ) {
		$this->__construct( $value === '1' );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'boolean';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->value ? 1 : 0;
	}

	/**
	 * Returns the boolean.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function getValue() {
		return $this->value;
	}

}
