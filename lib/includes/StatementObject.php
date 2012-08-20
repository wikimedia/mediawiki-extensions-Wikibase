<?php

namespace Wikibase;

/**
 * Class representing a Wikibase statement.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementObject implements Statement {

	/**
	 * The id of the entity.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $entityId;

	/**
	 * The type of the entity.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $entityType;

	/**
	 * The number of the statement within the entity.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $statementNumber;

	/**
	 * @since 0.1
	 *
	 * @var Claim
	 */
	protected $claim;

	/**
	 * @since 0.1
	 *
	 * @var References
	 */
	protected $references;

	/**
	 * @since 0.1
	 *
	 * @var integer, element of the Statement::RANK_ enum
	 */
	protected $rank;

	/**
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $number;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	protected function __construct() {

	}

	/**
	 * @see Statement::setEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function setEntity( Entity $entity ) {
		$this->entityId = $entity->getId();
		$this->entityType = $entity->getType();
		$this->number = 42; // TODO
	}

	/**
	 * @see Statement::getReferences
	 *
	 * @since 0.1
	 *
	 * @return References
	 */
	public function getReferences() {
		return $this->references;
	}

	/**
	 * @see Statement::setReferences
	 *
	 * @since 0.1
	 *
	 * @param References $references
	 */
	public function setReferences( References $references ) {
		$this->references = $references;
	}

	/**
	 * @see Statement::setRank
	 *
	 * @since 0.1
	 *
	 * @param integer $rank
	 */
	public function setRank( $rank ) {
		$this->rank = $rank;
	}

	/**
	 * @see Statement::getRank
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getRank() {
		return $this->rank;
	}

	/**
	 * @see Statement::getClaim
	 *
	 * @since 0.1
	 *
	 * @return Claim
	 */
	public function getClaim() {
		return $this->claim;
	}

	/**
	 * @see Statement::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( implode(
			'|',
			array(
				$this->entityType,
				$this->entityId,
				$this->number,
				$this->claim->getHash(),
				$this->references->getHash(),
			)
		) );
	}

	/**
	 * @see Statement::getNumber
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public static function newForEntity( Entity $entity ) {
		$statement = new static();

		$statement->setEntity( $entity );

		return $statement;
	}

}
