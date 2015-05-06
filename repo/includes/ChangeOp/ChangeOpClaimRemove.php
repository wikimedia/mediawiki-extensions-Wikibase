<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Summary;

/**
 * Class for statement remove operation.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class ChangeOpClaimRemove extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $guid;

	/**
	 * @return string
	 */
	public function getClaimGuid() {
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
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$this->removeStatement( $entity->getStatements(), $summary );

		return true;
	}

	/**
	 * @param StatementList $statements
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	private function removeStatement( StatementList $statements, Summary $summary = null ) {
		$statement = $statements->getFirstStatementWithGuid( $this->guid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have statement with GUID $this->guid" );
		}

		$statements->removeStatementsWithGuid( $this->guid );

		$removedSnak = $statement->getMainSnak();
		$this->updateSummary( $summary, 'remove', '', $this->getSummaryArgs( $removedSnak ) );
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
