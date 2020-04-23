<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Object representing a term index entry.
 *
 * @license GPL-2.0-or-later
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

	const FIELD_ENTITY = 'entityId';
	const FIELD_TYPE = 'termType';
	const FIELD_LANGUAGE = 'termLanguage';
	const FIELD_TEXT = 'termText';

	private static $fieldNames = [
		self::FIELD_ENTITY,
		self::FIELD_TYPE,
		self::FIELD_LANGUAGE,
		self::FIELD_TEXT,
	];

	/**
	 * @var string One of self::TYPE_* constants
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
	 * @var array All of the self::TYPE_* constants
	 */
	public static $validTermTypes = [ self::TYPE_ALIAS, self::TYPE_LABEL, self::TYPE_DESCRIPTION ];

	/**
	 * @param array $fields Associative array containing fields:
	 *        self::FIELD_TYPE => string, one of self::TYPE_* constants,
	 *        self::FIELD_LANGUAGE => string
	 *        self::FIELD_TEXT => string
	 *        self::FIELD_ENTITY => EntityId
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $fields ) {
		$this->assertConstructFieldsAreCorrect( $fields );
		$this->termType = $fields[self::FIELD_TYPE];
		$this->termLanguage = $fields[self::FIELD_LANGUAGE];
		$this->termText = $fields[self::FIELD_TEXT];
		$this->entityId = $fields[self::FIELD_ENTITY];
	}

	private function assertConstructFieldsAreCorrect( array $fields ) {
		Assert::parameter(
			count( $fields ) === count( self::$fieldNames ) &&
			empty( array_diff( self::$fieldNames, array_keys( $fields ) ) ),
			'$fields',
			'must contain the following keys: ' . implode( ', ', self::$fieldNames )
		);
		Assert::parameter(
			is_string( $fields[self::FIELD_TYPE] ) &&
			in_array( $fields[self::FIELD_TYPE], self::$validTermTypes ),
			'$fields["termType"]',
			'must be in :' . implode( ', ', self::$validTermTypes )
		);
		Assert::parameterType(
			'string',
			$fields[ self::FIELD_LANGUAGE ],
			'$fields["' . self::FIELD_LANGUAGE . '"]'
		);
		Assert::parameterType(
			'string',
			$fields[ self::FIELD_TEXT ],
			'$fields["' . self::FIELD_TEXT . '"]'
		);
		Assert::parameterType(
			EntityId::class,
			$fields[ self::FIELD_ENTITY ],
			'$fields["' . self::FIELD_ENTITY . '"]'
		);
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
				return $aValues[$n] <=> $bValues[$n];
			}
		}

		return 0;
	}

	private static function getFieldValuesForCompare( self $entry ) {
		return [
			self::FIELD_ENTITY => $entry->getEntityId()->getSerialization(),
			self::FIELD_TYPE => $entry->getTermType(),
			self::FIELD_LANGUAGE => $entry->getLanguage(),
			self::FIELD_TEXT => $entry->getText(),
		];
	}

	/**
	 * @return Term
	 */
	public function getTerm() {
		return new Term( $this->getLanguage(), $this->getText() );
	}

}
