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
	protected $rank = Statement::RANK_NORMAL;

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
	 *
	 * @param Claim $claim
	 * @param References|null $references
	 */
	protected function __construct( Claim $claim, References $references = null ) {
		$this->claim = $claim;
		$this->references = $references === null ? new ReferenceList() : $references;
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
	 * @throws \MWException
	 */
	public function setRank( $rank ) {
		$ranks = array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED );

		if ( !in_array( $rank, $ranks, true ) ) {
			throw new \MWException( 'Invalid rank specified for statement' );
		}

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
	 * @see Statement::setClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function setClaim( Claim $claim ) {
		$this->claim = $claim;
	}

	/**
	 * The hash generated here is globally unique, so can be used to
	 * identity the statement without further context.
	 *
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->entityId . $this->entityType . $this->number
			. sha1( implode(
				'|',
				array(
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
	 * Creates a new statement for the provided entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 * @param References|null $references
	 *
	 * @return Statement
	 */
	public static function newForEntity( Entity $entity, Claim $claim, References $references = null ) {
		$statement = new static( $claim, $references );

		$statement->setEntity( $entity );

		return $statement;
	}

}
