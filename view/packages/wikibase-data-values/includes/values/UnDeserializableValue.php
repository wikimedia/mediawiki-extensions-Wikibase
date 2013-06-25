<?php

namespace DataValues;

/**
 * Class representing a value that could not be unserialized for some reason.
 * It contains the raw native data structure representing the value,
 * as well as the originally intended value type and an error message.
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
 * @author Daniel Kinzler
 */
class UnDeserializableValue extends DataValueObject {

	/**
	 * @var mixed
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @since 0.1
	 *
	 * @param string $type  The originally intended type
	 * @param mixed  $data  The raw data structure
	 * @param string $error The error that occurred when processing the original data structure.
	 *
	 * @throws \InvalidArgumentException
	 * @internal param mixed $value
	 */
	public function __construct( $data, $type, $error ) {
		if ( !is_null( $type ) && !is_string( $type ) ) {
			throw new \InvalidArgumentException( '$type must be string or null' );
		}

		if ( is_object( $data ) ) {
			throw new \InvalidArgumentException( '$data must not be an object' );
		}

		if ( !is_string( $error ) ) {
			throw new \InvalidArgumentException( '$error must be a string' );
		}

		$this->data = $data;
		$this->type = $type;
		$this->error = $error;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @note: The serialization includes the intended type and the error message
	 *        along with the original data.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->type, $this->data, $this->error ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return StringValue
	 */
	public function unserialize( $value ) {
		list( $type, $data, $error ) = unserialize( $value );
		$this->__construct( $data, $type, $error );
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @note: this returns the original raw data structure.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return $this->data;
	}

	/**
	 * @see DataValue::toArray
	 *
	 * @note: This uses the originally intended type. This way, the native representation
	 *        does not model a UnDeserializableValue, but the originally intended type of value.
	 *        This allows for round trip compatibility with unknown types of data.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'value' => $this->getArrayValue(),
			'type' => $this->getTargetType(),
			'error' => $this->getReason(),
		);
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getType() {
		return 'bad';
	}

	/**
	 * Returns the value type that was intended for the bad data structure.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getTargetType() {
		return $this->type;
	}

	/**
	 * Returns a string describing the issue that caused the failure
	 * represented by this UnDeserializableValue object.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getReason() {
		return $this->error;
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return 0;
	}

	/**
	 * Returns the raw data structure.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->data;
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
		if ( !is_object( $value ) ) {
			return false;
		}

		return $value === $this ||
			( $value instanceof  UnDeserializableValue
				&& $value->data === $this->data
				&& $value->type === $this->type
				&& $value->error === $this->error
			);
	}

}
