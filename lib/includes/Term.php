<?php

namespace Wikibase;
use MWException;

/**
 * Object representing a term.
 * Terms can be incomplete.
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Term {

	/**
	 * Term type enum.
	 *
	 * @since 0.2
	 */
	const TYPE_LABEL = 'label';
	const TYPE_ALIAS = 'alias';
	const TYPE_DESCRIPTION = 'description';

	/**
	 * @since 0.2
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * @since 0.2
	 *
	 * @param array $fields
	 *
	 * @throws MWException
	 */
	public function __construct( array $fields = array() ) {
		foreach ( $fields as $name => $value ) {
			switch ( $name ) {
				case 'termType':
					$this->setType( $value );
					break;
				case 'termLanguage':
					$this->setLanguage( $value );
					break;
				case 'entityId':
					$this->setEntityId( $value );
					break;
				case 'entityType':
					$this->setEntityType( $value );
					break;
				case 'termText':
					$this->setText( $value );
					break;
				default:
					throw new MWException( 'Invalid term field provided' );
					break;
			}
		}
	}

	/**
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @throws MWException
	 */
	public function setType( $type ) {
		if ( !in_array( $type, array( self::TYPE_ALIAS, self::TYPE_LABEL, self::TYPE_DESCRIPTION ), true ) ) {
			throw new MWException( 'Invalid term type provided' );
		}

		$this->fields['termType'] = $type;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getType() {
		return array_key_exists( 'termType', $this->fields ) ? $this->fields['termType'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @param string $languageCode
	 *
	 * @throws MWException
	 */
	public function setLanguage( $languageCode ) {
		if ( !is_string( $languageCode ) ) {
			throw new MWException( 'Language code can only be a string' );
		}

		$this->fields['termLanguage'] = $languageCode;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getLanguage() {
		return array_key_exists( 'termLanguage', $this->fields ) ? $this->fields['termLanguage'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @param string $text
	 *
	 * @throws MWException
	 */
	public function setText( $text ) {
		if ( !is_string( $text ) ) {
			throw new MWException( 'Term text code can only be a string' );
		}

		$this->fields['termText'] = $text;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getText() {
		return array_key_exists( 'termText', $this->fields ) ? $this->fields['termText'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @throws MWException
	 */
	public function setEntityType( $type ) {
		if ( !is_string( $type ) ) {
			throw new MWException( 'Entity type code can only be a string' );
		}

		$this->fields['entityType'] = $type;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getEntityType() {
		return array_key_exists( 'entityType', $this->fields ) ? $this->fields['entityType'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @param integer $id
	 *
	 * @throws MWException
	 */
	public function setEntityId( $id ) {
		if ( !is_int( $id ) ) {
			throw new MWException( 'Entity id code can only be an integer' );
		}

		$this->fields['entityId'] = $id;
	}

	/**
	 * @since 0.2
	 *
	 * @return integer|null
	 */
	public function getEntityId() {
		return array_key_exists( 'entityId', $this->fields ) ? $this->fields['entityId'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getNormalizedText() {
		$text = $this->getText();
		return $text === null? null : self::normalizeText( $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function normalizeText( $text ) {
		return strtolower( Utils::squashToNFC( $text ) );
	}

}
