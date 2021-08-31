<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * Represents a single Wikibase property.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Properties
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Property implements
	StatementListProvidingEntity,
	FingerprintProvider,
	StatementListHolder,
	LabelsProvider,
	DescriptionsProvider,
	AliasesProvider,
	ClearableEntity
{

	public const ENTITY_TYPE = 'property';

	/**
	 * @var PropertyId|null
	 */
	private $id;

	/**
	 * @var Fingerprint
	 */
	private $fingerprint;

	/**
	 * @var string The data type of the property.
	 */
	private $dataTypeId;

	/**
	 * @var StatementList
	 */
	private $statements;

	/**
	 * @since 1.0
	 *
	 * @param PropertyId|null $id
	 * @param Fingerprint|null $fingerprint
	 * @param string $dataTypeId The data type of the property. Not to be confused with the data
	 *  value type.
	 * @param StatementList|null $statements Since 1.1
	 */
	public function __construct(
		?PropertyId $id,
		?Fingerprint $fingerprint,
		$dataTypeId,
		StatementList $statements = null
	) {
		$this->id = $id;
		$this->fingerprint = $fingerprint ?: new Fingerprint();
		$this->setDataTypeId( $dataTypeId );
		$this->statements = $statements ?: new StatementList();
	}

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 0.1 return type changed in 0.3
	 *
	 * @return PropertyId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @since 0.5, can be null since 1.0
	 *
	 * @param PropertyId|null $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( !( $id instanceof PropertyId ) && $id !== null ) {
			throw new InvalidArgumentException( '$id must be a PropertyId or null' );
		}

		$this->id = $id;
	}

	/**
	 * @since 0.7.3
	 *
	 * @return Fingerprint
	 */
	public function getFingerprint() {
		return $this->fingerprint;
	}

	/**
	 * @since 0.7.3
	 *
	 * @param Fingerprint $fingerprint
	 */
	public function setFingerprint( Fingerprint $fingerprint ) {
		$this->fingerprint = $fingerprint;
	}

	/**
	 * @see LabelsProvider::getLabels
	 *
	 * @since 6.0
	 *
	 * @return TermList
	 */
	public function getLabels() {
		return $this->fingerprint->getLabels();
	}

	/**
	 * @see DescriptionsProvider::getDescriptions
	 *
	 * @since 6.0
	 *
	 * @return TermList
	 */
	public function getDescriptions() {
		return $this->fingerprint->getDescriptions();
	}

	/**
	 * @see AliasesProvider::getAliasGroups
	 *
	 * @since 6.0
	 *
	 * @return AliasGroupList
	 */
	public function getAliasGroups() {
		return $this->fingerprint->getAliasGroups();
	}

	/**
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function setLabel( $languageCode, $value ) {
		$this->fingerprint->setLabel( $languageCode, $value );
	}

	/**
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDescription( $languageCode, $value ) {
		$this->fingerprint->setDescription( $languageCode, $value );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 */
	public function setAliases( $languageCode, array $aliases ) {
		$this->fingerprint->setAliasGroup( $languageCode, $aliases );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $dataTypeId The data type of the property. Not to be confused with the data
	 *  value type.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDataTypeId( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId must be a string' );
		}

		$this->dataTypeId = $dataTypeId;
	}

	/**
	 * @since 0.4
	 *
	 * @return string Returns the data type of the property (property type). Not to be confused with
	 *  the data value type.
	 */
	public function getDataTypeId() {
		return $this->dataTypeId;
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string Returns the entity type "property".
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @since 0.3
	 *
	 * @param string $dataTypeId The data type of the property. Not to be confused with the data
	 *  value type.
	 *
	 * @return self
	 */
	public static function newFromType( $dataTypeId ) {
		return new self( null, null, $dataTypeId );
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @since 0.1
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->dataTypeId === $target->dataTypeId
			&& $this->fingerprint->equals( $target->fingerprint )
			&& $this->statements->equals( $target->statements );
	}

	/**
	 * Returns if the Property has no content.
	 * Having an id and type set does not count as having content.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->fingerprint->isEmpty()
			&& $this->statements->isEmpty();
	}

	/**
	 * @since 1.1
	 *
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statements;
	}

	/**
	 * @since 1.1
	 *
	 * @param StatementList $statements
	 */
	public function setStatements( StatementList $statements ) {
		$this->statements = $statements;
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @since 0.1
	 *
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		$this->fingerprint = clone $this->fingerprint;
		$this->statements = clone $this->statements;
	}

	/**
	 * @since 7.5
	 */
	public function clear() {
		$this->fingerprint = new Fingerprint();
		$this->statements = new StatementList();
	}

}
