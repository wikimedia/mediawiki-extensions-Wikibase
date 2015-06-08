<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
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
class ChangeOpRemoveStatement extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $guid;

	/**
	 * @return string
	 */
	public function getStatementGuid() {
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

		$statements = $this->removeStatement( $entity->getStatements()->toArray(), $summary );
		$this->setStatements( $entity, $statements );

		return true;
	}

	/**
	 * @param Statement[] $statements
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 * @return Statement[]
	 */
	private function removeStatement( array $statements, Summary $summary = null ) {
		$newStatements = array();
		$removedStatement = null;

		foreach ( $statements as $statement ) {
			if ( $statement->getGuid() === $this->guid && $removedStatement === null ) {
				$removedStatement = $statement;
			} else {
				$newStatements[] = $statement;
			}
		}

		if ( $removedStatement === null ) {
			throw new ChangeOpException( "Entity does not have statement with GUID $this->guid" );
		}

		$removedSnak = $removedStatement->getMainSnak();
		$this->updateSummary( $summary, 'remove', '', $this->getSummaryArgs( $removedSnak ) );

		return $newStatements;
	}

	/**
	 * @param Entity $entity
	 * @param Statement[] $statements
	 *
	 * @throws InvalidArgumentException
	 */
	private function setStatements( Entity $entity, array $statements ) {
		$statementList = new StatementList( $statements );

		if ( $entity instanceof Item ) {
			$entity->setStatements( $statementList );
		} elseif ( $entity instanceof Property ) {
			$entity->setStatements( $statementList );
		} else {
			throw new InvalidArgumentException( '$entity must be an Item or Property' );
		}
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
