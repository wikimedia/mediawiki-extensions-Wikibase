<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\Summary;

/**
 * Class for statement remove operation.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class ChangeOpRemoveStatement extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $guid;

	/**
	 * @return string
	 */
	public function getGuid() {
		return $this->guid;
	}

	/**
	 * Constructs a new mainsnak change operation
	 *
	 * @since 0.5
	 *
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $guid ) {
		if ( !is_string( $guid ) || $guid === '' ) {
			throw new InvalidArgumentException( '$guid must be a non-empty string' );
		}

		$this->guid = $guid;
	}

	/**
	 * @see ChangeOp::apply
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListHolder ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListHolder' );
		}

		$statements = $entity->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $this->guid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have statement with GUID $this->guid" );
		}

		$statements->removeStatementsWithGuid( $this->guid );
		$entity->setStatements( $statements );

		$removedSnak = $statement->getMainSnak();
		$this->updateSummary( $summary, 'remove', '', $this->getSummaryArgs( $removedSnak ) );

		return true;
	}

	/**
	 * @param Snak $mainSnak
	 *
	 * @return array
	 */
	private function getSummaryArgs( Snak $mainSnak ) {
		$propertyId = $mainSnak->getPropertyId();
		return array( array( $propertyId->getSerialization() => $mainSnak ) );
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 *
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		//TODO: move validation logic from apply() here.
		return parent::validate( $entity );
	}

}
