<?php

namespace Wikibase;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * Object representing a term index entry.
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

	private static $fieldNames = [
		'entityId',
		'termType',
		'termLanguage',
		'termText',
	];

	/**
	 * @var string, one of self::TYPE_* constants
	 */
	private $termType;

	/**
	 * @var string
	 */
	private $termLanguage;

	/**
	 * @var string
	 */
	private $termText;

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @since 0.2
	 *
	 * @param array $fields, containing fields:
	 *        'termType' => string, one of self::TYPE_* constants,
	 *        'termLanguage' => string
	 *        'termText' => string
	 *        'entityId' => EntityId
	 *
	 * @throws MWException
	 */
	public function __construct( array $fields ) {
		if ( count( $fields ) !== count( self::$fieldNames ) ) {
			throw new MWException( 'Invalid term index entry field provided' );
		}

		$this->setType( $fields['termType'] );
		$this->setLanguage( $fields['termLanguage'] );
		$this->setText( $fields['termText'] );
		$this->setEntityId( $fields['entityId'] );
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
	 * @return string
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
	 * @return string
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
			throw new MWException( 'Term text can only be a string' );
		}

		$this->termText = $text;
	}

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getText() {
		return $this->termText;
	}

	private function setEntityId( EntityId $id ) {
		$this->entityId = $id;
	}

	/**
	 * @since 0.2
	 *
	 * @throws RuntimeException
	 * @return EntityId
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
			if ( $aValues[$n] !== $bValues[$n] ) {
				return $aValues[$n] > $bValues[$n] ? 1 : -1;
			}
		}

		return 0;
	}

	private static function getFieldValuesForCompare( self $entry ) {
		return [
			'entityId' => $entry->getEntityId()->getSerialization(),
			'termType' => $entry->getType(),
			'termLanguage' => $entry->getLanguage(),
			'termText' => $entry->getText(),
		];
	}

	/**
	 * @return Term
	 */
	public function getTerm() {
		return new Term( $this->getLanguage(), $this->getText() );
	}

}
