<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\Summary;

/**
 * Class for qualifier removal change operation
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpQualifierRemove extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var string
	 */
	private $snakHash;

	/**
	 * Constructs a new qualifier removal change operation
	 *
	 * @since 0.5
	 *
	 * @param string $statementGuid
	 * @param string $snakHash
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statementGuid, $snakHash ) {
		if ( !is_string( $statementGuid ) || $statementGuid === '' ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $snakHash ) || $snakHash === '' ) {
			throw new InvalidArgumentException( '$snakHash needs to be a string and must not be empty' );
		}

		$this->statementGuid = $statementGuid;
		$this->snakHash = $snakHash;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListHolder ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListHolder' );
		}

		$statements = $entity->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $this->statementGuid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have a statement with GUID $this->statementGuid" );
		}

		$qualifiers = $statement->getQualifiers();

		$this->removeQualifier( $qualifiers, $summary );

		$statement->setQualifiers( $qualifiers );
		$entity->setStatements( $statements );
	}

	/**
	 * @param SnakList $qualifiers
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function removeQualifier( SnakList $qualifiers, Summary $summary = null ) {
		if ( !$qualifiers->hasSnakHash( $this->snakHash ) ) {
			throw new ChangeOpException( "Qualifier with hash $this->snakHash does not exist" );
		}
		$removedQualifier = $qualifiers->getSnak( $this->snakHash );
		$qualifiers->removeSnakHash( $this->snakHash );
		$this->updateSummary( $summary, 'remove', '', $this->getSnakSummaryArgs( $removedQualifier ) );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return array
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		return array( array( $propertyId->getSerialization() => $snak ) );
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		//TODO: move validation logic from apply() here.
		return parent::validate( $entity );
	}

}
