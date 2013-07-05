<?php

namespace Wikibase;
use MWException;
use Wikibase\Client\WikibaseClient;
use Wikibase\Repo\WikibaseRepo;

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
	protected $fields = array();

	protected static $fieldNames = array(
		'entityType',
		'entityId',
		'termType',
		'termLanguage',
		'termText',
	);

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
		$lang = $this->getLanguage();
		return $text === null? null : self::normalizeText( $text, $lang );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $text
	 * @param string $lang language code of the text's language, may be used
	 *        for specialized normalization.
	 *
	 * @return string
	 *
	 * @todo: Move this to TermSqlIndex
	 */
	public static function normalizeText( $text, $lang = 'en' ) {
		if ( $text === '' ) {
			return '';
		}

		//FIXME: move normalizeText to TermSqlIndex to avoid this mess!
		if ( class_exists( 'Wikibase\Repo\WikibaseRepo' ) ) {
			$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();
		} elseif ( class_exists( 'Wikibase\Client\WikibaseClient' ) ) {
			$normalizer = WikibaseClient::getDefaultInstance()->getStringNormalizer();
		} else {
			throw new \RuntimeException( "Found nither WikibaseRepo not WikibaseClient" );
		}

		// composed normal form
		$nfcText = $normalizer->cleanupToNFC( $text );

		if ( !is_string( $nfcText ) || $nfcText === '' ) {
			wfWarn( "Unicode normalization failed for `$text`" );
		}

		// \p{Z} - whitespace
		// \p{C} - control chars
		// WARNING: *any* invalid UTF8 sequence causes preg_replace to return an empty string.
		$strippedText = $nfcText;
		$strippedText = preg_replace( '/[\p{Cc}\p{Cf}\p{Cn}\p{Cs}]+/u', ' ', $strippedText );
		$strippedText = preg_replace( '/^[\p{Z}]+|[\p{Z}]+$/u', '', $strippedText );

		if ( $strippedText === '' ) {
			// NOTE: This happens when there is only whitespace in the string.
			//       However, preg_replace will also return an empty string if it
			//       encounters any invalid utf-8 sequence.
			return '';
		}

		//TODO: Use Language::lc to convert to lower case.
		//      But that requires us to load ALL the language objects,
		//      which loads ALL the messages, which makes us run out
		//      of RAM (see bug 41103).
		$normalized = mb_strtolower( $strippedText, 'UTF-8' );

		if ( !is_string( $normalized ) || $normalized === '' ) {
			wfWarn( "mb_strtolower normalization failed for `$strippedText`" );
		}

		return $normalized;
	}

	/**
	 * Returns true if this Term object is equals to $that. This Term object is considered
	 * equal to $that if $that is also an instance of Term, and $that->fields contains the
	 * same values for the same fields as $this->fields.
	 *
	 * @param mixed $that The object to check for equality.
	 *
	 * @return bool If $that is equal to this Term object.
	 */
	public function equals( $that ) {
		if ( $this === $that ) {
			return true;
		} else if ( !( $that instanceof Term ) ) {
			return false;
		} else {
			if ( count( $this->fields ) != count( $that->fields ) ) {
				return false;
			}

			/* @var Term $that */
			foreach ( $this->fields as $k => $v ) {
				if ( !isset( $that->fields[$k] ) || $that->fields[$k] !== $v ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Imposes an canonical but arbitrary order on Term objects.
	 * Useful for sorting lists of terms for comparison.
	 *
	 * @param Term $a
	 * @param Term $b
	 *
	 * @return int Returns 1 if $a is greater than $b, -1 if $b is greater than $a, and 0 otherwise.
	 */
	public static function compare( Term $a, Term $b ) {
		foreach ( self::$fieldNames as $n ) {
			if ( !isset( $a->fields[$n] ) ) {
				if ( isset( $b->fields[$n] ) ) {
					return -1;
				}
			} elseif ( !isset( $b->fields[$n] ) ) {
				if ( isset( $a->fields[$n] ) ) {
					return 1;
				}
			} elseif ( $a->fields[$n] > $b->fields[$n] ) {
				return 1;
			} elseif ( $a->fields[$n] < $b->fields[$n] ) {
				return -1;
			}
		}

		return 0;
	}
}
