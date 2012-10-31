<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Class representing a monolingual text value.
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
class MonolingualTextValue extends DataValueObject {

	/**
	 * String value.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * Language code.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, $value ) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'Can only construct MonolingualTextValue with a string language code' );
		}
		elseif ( $languageCode === '' ) {
			throw new InvalidArgumentException( 'Can only construct MonolingualTextValue with a language code of non-zero length' );
		}

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'Can only construct MonolingualTextValue with a string value' );
		}

		$this->value = $value;
		$this->language = $languageCode;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->language, $this->value ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return MonolingualTextValue
	 */
	public function unserialize( $value ) {
		list ( $languageCode, $value ) = unserialize( $value );
		$this->__construct( $languageCode, $value );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'monolingualtext';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->language . $this->value;
	}

	/**
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return MonolingualTextValue
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * Returns the text.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getText() {
		return $this->value;
	}

	/**
	 * Returns the language code.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->language;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return array(
			'text' => $this->value,
			'language' => $this->language,
		);
	}

}
