<?php

namespace Wikibase;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\WikibaseRepo;

/**
 * Object representing a term index entry.
 * Term index entries can be incomplete.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 */
class TermIndexEntry {

	/**
	 * Term type enum.
	 *
	 * @since 0.2
	 */
	const TYPE_LABEL = 'label';
	const TYPE_ALIAS = 'alias';
	const TYPE_DESCRIPTION = 'description';

	/**
	 * @var array
	 */
	private $fields = array();

	private static $fieldNames = array(
		'entityType',
		'entityId',
		'termType',
		'termLanguage',
		'termText',
		'termWeight',
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
				case 'termWeight':
					$this->setWeight( $value );
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
	 * @since 0.5
	 *
	 * @param float $weight
	 *
	 * @throws MWException
	 */
	public function setWeight( $weight ) {
		if ( !is_float( $weight ) ) {
			throw new MWException( 'Term weight code can only be a float' );
		}

		$this->fields['termWeight'] = $weight;
	}

	/**
	 * @since 0.5
	 *
	 * @return float|null
	 */
	public function getWeight() {
		return array_key_exists( 'termWeight', $this->fields ) ? $this->fields['termWeight'] : null;
	}

	/**
	 * @param string $entityType
	 *
	 * @throws MWException
	 */
	private function setEntityType( $entityType ) {
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
	 * @param int $id
	 *
	 * @throws MWException
	 */
	private function setNumericId( $id ) {
		if ( !is_int( $id ) ) {
			throw new MWException( 'Numeric ID can only be an integer' );
		}

		$this->fields['entityId'] = $id;
	}

	/**
	 * @since 0.5
	 *
	 * @return int|null
	 */
	public function getNumericId() {
		return array_key_exists( 'entityId', $this->fields ) ? $this->fields['entityId'] : null;
	}

	/**
	 * @since 0.2
	 *
	 * @throws RuntimeException
	 * @return EntityId|null
	 */
	public function getEntityId() {
		$entityType = $this->getEntityType();
		$numericId = $this->getNumericId();

		if ( $entityType !== null && $numericId !== null ) {
			// TODO: This does not belong to a value object. Provide entityId object to constructor, so service will be obsolete.
			if ( defined( 'WB_VERSION' ) ) {
				$entityIdComposer = WikibaseRepo::getDefaultInstance()->getEntityIdComposer();
			} elseif ( defined( 'WBC_VERSION' ) ) {
				$entityIdComposer = WikibaseClient::getDefaultInstance()->getEntityIdComposer();
			} else {
				throw new RuntimeException( 'Need either client or repo loaded' );
			}

			try {
				return $entityIdComposer->composeEntityId( $entityType, $numericId );
			} catch ( InvalidArgumentException $ex ) {
				wfLogWarning( 'Unsupported entity type "' . $entityType . '"' );
			}
		}

		return null;
	}

	/**
	 * Imposes an canonical but arbitrary order on Term objects.
	 * Useful for sorting lists of terms for comparison.
	 * This comparison DOES NOT use termWeight
	 *
	 * @param self $a
	 * @param self $b
	 *
	 * @return int Returns 1 if $a is greater than $b, -1 if $b is greater than $a, and 0 otherwise.
	 */
	public static function compare( self $a, self $b ) {
		$fieldNames = self::$fieldNames;
		unset( $fieldNames[array_search( 'termWeight', $fieldNames )] );

		foreach ( $fieldNames as $n ) {
			$exists = array_key_exists( $n, $a->fields );

			if ( $exists !== array_key_exists( $n, $b->fields ) ) {
				return $exists ? 1 : -1;
			} elseif ( $exists && $a->fields[$n] !== $b->fields[$n] ) {
				return $a->fields[$n] > $b->fields[$n] ? 1 : -1;
			}
		}

		return 0;
	}

	/**
	 * @return Term
	 * @throws MWException
	 */
	public function getTerm() {
		try {
			return new Term( $this->getLanguage(), $this->getText() );
		} catch ( InvalidArgumentException $e ) {
			throw new MWException( 'Can not construct Term from partial TermIndexEntry' );
		}
	}

}
