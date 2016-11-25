<?php

namespace Wikibase;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

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

	private static $fieldNames = array(
		'entityType',
		'entityId',
		'termType',
		'termLanguage',
		'termText',
	);

	/**
	 * @var string|null, one of self::TYPE_* constants
	 */
	private $termType;

	/**
	 * @var string|null
	 */
	private $termLanguage;

	/**
	 * @var string|null
	 */
	private $termText;

	/**
	 * @var EntityId|null
	 */
	private $entityId;

	/**
	 * @var string|null
	 */
	private $entityType;

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

		$this->termType = $termType;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getType() {
		return $this->termType;
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

		$this->termLanguage = $languageCode;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getLanguage() {
		return $this->termLanguage;
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

		$this->termText = $text;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getText() {
		return $this->termText;
	}

	/**
	 * @param string $entityType
	 *
	 * @throws MWException
	 */
	private function setEntityType( $entityType ) {
		if ( $this->entityId !== null ) {
			if ( $this->entityId->getEntityType() !== $entityType ) {
				throw new MWException(
					'Cannot set entity type to "' . $entityType . '"" as it does not match the type of entity id: "' .
					$this->entityId->getEntityType() . '"'
				);
			}
			return;
		}

		if ( !is_string( $entityType ) ) {
			throw new MWException( 'Entity type code can only be a string' );
		}

		$this->entityType = $entityType;
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getEntityType() {
		if ( $this->entityId !== null ) {
			return $this->entityId->getEntityType();
		}
		return $this->entityType;
	}

	private function setEntityId( EntityId $id ) {
		$this->entityId = $id;
		$this->entityType = $id->getEntityType();
	}

	/**
	 * @since 0.2
	 *
	 * @throws RuntimeException
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * Imposes an canonical but arbitrary order on Term objects.
	 * Useful for sorting lists of terms for comparison.
	 *
	 * @param self $a
	 * @param self $b
	 *
	 * @return int Returns 1 if $a is greater than $b, -1 if $b is greater than $a, and 0 otherwise.
	 */
	public static function compare( self $a, self $b ) {
		$aValues = self::getFieldValuesForCompare( $a );
		$bValues = self::getFieldValuesForCompare( $b );

		foreach ( self::$fieldNames as $n ) {
			$aDefined = $aValues[$n] !== null;
			$bDefined = $bValues[$n] !== null;

			if ( $aDefined !== $bDefined ) {
				return $aDefined ? 1 : -1;
			}
			if ( $aDefined ) {
				if ( $aValues[$n] !== $bValues[$n] ) {
					return $aValues[$n] > $bValues[$n] ? 1 : -1;
				}
			}
		}

		return 0;
	}

	private static function getFieldValuesForCompare( self $entry ) {
		$entityId = $entry->getEntityId();
		return [
			'entityType' => $entry->getEntityType(),
			'entityId' => $entityId !== null ? $entityId->getSerialization() : null,
			'termType' => $entry->getType(),
			'termLanguage' => $entry->getLanguage(),
			'termText' => $entry->getText(),
		];
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
