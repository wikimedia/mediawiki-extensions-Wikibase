<?php

namespace Wikibase;

use Wikimedia\Assert\Assert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Object representing a term index entry.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 */
class TermIndexEntry {

	/**
	 * Term type enum.
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
	 * @param array $fields, containing fields:
	 *        'termType' => string, one of self::TYPE_* constants,
	 *        'termLanguage' => string
	 *        'termText' => string
	 *        'entityId' => EntityId
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $fields ) {
		Assert::parameter(
			count( $fields ) === count( self::$fieldNames ) &&
				empty( array_diff( self::$fieldNames, array_keys( $fields ) ) ),
			'$fields',
			'must contain the following keys: termType, termLanguage, termText, entityId'
		);
		Assert::parameter(
			is_string( $fields['termType'] ) &&
				in_array( $fields['termType'], [ self::TYPE_ALIAS, self::TYPE_LABEL, self::TYPE_DESCRIPTION ] ),
			'$fields["termType"]',
			'must be self::TYPE_ALIAS, self::TYPE_LABEL, or self::TYPE_DESCRIPTION '
		);
		Assert::parameterType( 'string', $fields['termLanguage'], '$fields["termLanguage"]' );
		Assert::parameterType( 'string', $fields['termText'], '$fields["termText"]' );
		Assert::parameterType( EntityId::class, $fields['entityId'], '$fields["entityId"]' );

		$this->termType = $fields['termType'];
		$this->termLanguage = $fields['termLanguage'];
		$this->termText = $fields['termText'];
		$this->entityId = $fields['entityId'];
	}

	/**
	 * @return string
	 */
	public function getTermType() {
		return $this->termType;
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->termLanguage;
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->termText;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityId->getEntityType();
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
			'termType' => $entry->getTermType(),
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
