<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Class representing a multilingual text value.
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
class MultilingualTextValue extends DataValueObject {

	/**
	 * Array with language codes pointing to their associated texts.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $texts;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param array $texts
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $texts ) {
		foreach ( $texts as $languageCode => $text ) {
			if ( !is_string( $languageCode ) ) {
				throw new InvalidArgumentException( 'Can only construct MultilingualTextValue from string language codes' );
			}

			if ( !is_string( $text ) ) {
				throw new InvalidArgumentException( 'Can only construct MultilingualTextValue from string text values' );
			}
		}

		$this->texts = $texts;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->texts );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return MultilingualTextValue
	 */
	public function unserialize( $value ) {
		$this->__construct( unserialize( $value ) );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'multilingualtext';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return empty( $this->texts ) ? '' : reset( $this->texts );
	}

	/**
	 * Returns the texts in an associative array with language codes pointing to their associated texts.
	 * This is the same format as the one that can be fed to the constructor.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getTexts() {
		return $this->texts;
	}

	/**
	 * Returns the texts as an array of monolingual text values.
	 *
	 * @since 0.1
	 *
	 * @return array of MonolingualTextValue
	 */
	public function getMonolingualTextValues() {
		$values = array();

		foreach ( $this->texts as $languageCode => $text ) {
			$values[] = new MonolingualTextValue( $languageCode, $text );
		}

		return $values;
	}

	/**
	 * Returns the multilingual text value
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getValue() {
		return $this;
	}

}
