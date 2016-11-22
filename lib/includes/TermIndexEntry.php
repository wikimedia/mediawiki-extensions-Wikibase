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
		$unexpectedFields = array_diff_key( $fields, array_flip( self::$fieldNames ) );
		if ( $unexpectedFields ) {
			throw new MWException( 'Invalid term field provided' );
		}

		if ( array_key_exists( 'termType', $fields ) ) {
			$this->setType( $fields['termType'] );
		}
		if ( array_key_exists( 'termLanguage', $fields ) ) {
			$this->setLanguage( $fields['termLanguage'] );
		}
		if ( array_key_exists( 'entityId', $fields ) ) {
			$this->setEntityId( $fields['entityId'] );
		}
		if ( array_key_exists( 'entityType', $fields ) ) {
			$this->setEntityType( $fields['entityType'] );
		}
		if ( array_key_exists( 'termText', $fields ) ) {
			$this->setText( $fields['termText'] );
		}
		if ( array_key_exists( 'termWeight', $fields ) ) {
			$this->setWeight( $fields['termWeight'] );
		}
	}

	/**
	 * @param string $termType
	 *
	 * @throws MWException
	 */
	private function setType( $termType ) {
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
	 * @param string $languageCode
	 *
	 * @throws MWException
	 */
	private function setLanguage( $languageCode ) {
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
	 * @param string $text
	 *
	 * @throws MWException
	 */
	private function setText( $text ) {
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
	 * @param float $weight
	 *
	 * @throws MWException
	 */
	private function setWeight( $weight ) {
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
		if ( isset( $this->fields['entityId'] ) ) {
			if ( $this->fields['entityId']->getEntityType() !== $entityType ) {
				throw new MWException(
					'Cannot set entity type to "' . $entityType . '"" as it does not match the type of entity id: "' .
					$this->fields['entityId']->getEntityType() . '"'
				);
			}
			return;
		}

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
		if ( array_key_exists( 'entityId', $this->fields ) ) {
			return $this->fields['entityId']->getEntityType();
		}
		return array_key_exists( 'entityType', $this->fields ) ? $this->fields['entityType'] : null;
	}

	private function setEntityId( EntityId $id ) {
		$this->fields['entityId'] = $id;
		$this->fields['entityType'] = $id->getEntityType();
	}

	/**
	 * @since 0.2
	 *
	 * @throws RuntimeException
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return array_key_exists( 'entityId', $this->fields ) ? $this->fields['entityId'] : null;
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
			}
			if ( $exists ) {
				$aValue = $n !== 'entityId' ? $a->fields[$n] : $a->fields[$n]->getSerialization();
				$bValue = $n !== 'entityId' ? $b->fields[$n] : $b->fields[$n]->getSerialization();
				if ( $aValue !== $bValue ) {
					return $aValue > $bValue ? 1 : -1;
				}
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
