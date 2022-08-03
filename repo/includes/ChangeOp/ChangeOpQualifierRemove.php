<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;

/**
 * Class for qualifier removal change operation
 *
 * @license GPL-2.0-or-later
 * @author Addshore
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
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$statements = $entity->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $this->statementGuid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have a statement with GUID $this->statementGuid" );
		}

		$qualifiers = $statement->getQualifiers();

		$this->removeQualifier( $qualifiers, $summary );

		$statement->setQualifiers( $qualifiers );

		return new GenericChangeOpResult( $entity->getId(), true );
	}

	/**
	 * @param SnakList $qualifiers
	 * @param Summary|null $summary
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
		return [ [ $propertyId->getSerialization() => $snak ] ];
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result Always successful.
	 */
	public function validate( EntityDocument $entity ) {
		//TODO: move validation logic from apply() here.
		return Result::newSuccess();
	}

}
