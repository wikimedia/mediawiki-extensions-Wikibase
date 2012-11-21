<?php

namespace DataValues;

/**
 * Base for objects that represent a single data value.
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
abstract class DataValueObject implements DataValue {

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( serialize( $this ) );
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.1
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public function equals( $value ) {
		return $value === $this ||
			( is_object( $value ) && get_class( $value ) == get_called_class() && serialize( $value ) === serialize( $this ) );
	}

	/**
	 * @see Copyable::getCopy
	 *
	 * @since 0.1
	 *
	 * @return DataValue
	 */
	public function getCopy() {
		return unserialize( serialize( $this ) );
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return $this->getValue();
	}

	/**
	 * @see DataValue::toArray
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'value' => $this->getArrayValue(),
			'type' => $this->getType(),
		);
	}

}
