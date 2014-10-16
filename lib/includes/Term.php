<?php

namespace Wikibase;

use MWException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * Object representing a term.
 * Terms can be incomplete.
 *
 * @since 0.2
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
					$this->setNumericId( $value );
					break;
				case 'entityType':
					$this->setEntityType( $value );
					break;
				case 'termText':
					$this->setText( $value );
					break;
				default:
					throw new MWException( 'Invalid term field provided' );
			}
		}
	}

	/**
	 * @since 0.2
	 *
	 * @param string $termType
	 *
	 * @throws MWException
	 */
	public function setType( $termType ) {
		if ( !in_array( $termType, array( self::TYPE_ALIAS, self::TYPE_LABEL, self::TYPE_DESCRIPTION ), true ) ) {
			throw new MWException( 'Invalid term type provided' );
		}

		$this->fields['termType'] = $termType;
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
	 * @param string $entityType
	 *
	 * @throws MWException
	 */
	public function setEntityType( $entityType ) {
		if ( !is_string( $entityType ) ) {
			throw new MWException( 'Entity type code can only be a string' );
		}

		$this->fields['entityType'] = $entityType;
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
	 * @deprecated Please avoid using this.
	 *
	 * @param int $id
	 *
	 * @throws MWException
	 */
	public function setNumericId( $id ) {
		if ( !is_int( $id ) ) {
			throw new MWException( 'Numeric ID can only be an integer' );
		}

		$this->fields['entityId'] = $id;
	}

	/**
	 * @see TermSqlIndex::getMatchingIDs
	 *
	 * @since 0.2
	 *
	 * @return EntityId|null
	 */
	public function getEntityId() {
		$entityType = $this->getEntityType();

		if ( $entityType !== null && array_key_exists( 'entityId', $this->fields ) ) {
			$numericId = $this->fields['entityId'];

			// FIXME: this only works for items and properties
			return LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId );
		}

		return null;
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
		} else if ( !( $that instanceof self ) ) {
			return false;
		} else {
			if ( count( $this->fields ) != count( $that->fields ) ) {
				return false;
			}

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
