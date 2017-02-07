<?php

namespace Wikibase\Client\Usage;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Value object representing the usage of statments of an entity. This includes information about
 * how the entity is used, but not where. ??
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Andrew Hall
 */
class StatementUsage {


	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var PropertyId
	 */
	private $propertyId;

	/**
	 * @var bool
	 */
	private $statementExists;

	/**
	 * @param EntityId $entityId
	 * @param propertyId $propertyId
	 * @param bool $statementExists
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityId $entityId, $propertyId, $statementExists) {
		$this->entityId = $entityId;
		$this->propertyId = $propertyId;
		$this->statementExists = $statementExists;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @return bool
	 */
	public function getStatementExists() {
		return $this->statementExists;
	}

	/**
	 * @return string
	 */
	public function getIdentityString() {
		$formatedStatementExists = $this->statementExists ? 't' : 'f';
		return $this->getEntityId()->getSerialization() . '#S.' . $this->propertyId->getSerialization() . "." . $formatedStatementExists;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getIdentityString();
	}

	/**
	 * @return array array( 'entityId' => $entityId, 'propertyId' => $propertyId, 'propertyExists' => $propertyExists )
	 */
	public function asArray() {
		return array(
			'entityId' => $this->entityId->getSerialization(),
			'propertyId' => $this->propertyId,
			'propertyExists' => $this->propertyExists,
			'aspect' => 'S'
		);
	}
}
