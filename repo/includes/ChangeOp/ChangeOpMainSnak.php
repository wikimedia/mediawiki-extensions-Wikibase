<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Summary;

/**
 * Class for mainsnak change operation
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpMainSnak extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var Snak
	 */
	private $snak;

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new mainsnak change operation
	 *
	 * @since 0.4
	 *
	 * @param string $statementGuid
	 * @param Snak $snak
	 * @param GuidGenerator $guidGenerator
	 * @param SnakValidator $snakValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$statementGuid,
		Snak $snak,
		GuidGenerator $guidGenerator,
		SnakValidator $snakValidator
	) {
		if ( !is_string( $statementGuid ) ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string' );
		}

		$this->statementGuid = $statementGuid;
		$this->snak = $snak;
		$this->guidGenerator = $guidGenerator;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @return string
	 */
	public function getStatementGuid() {
		return $this->statementGuid;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new claim with $snak as mainsnak gets added when $claimGuid is empty and $snak is set
	 * - the claim's mainsnak gets set to $snak when $claimGuid and $snak are set
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$statements = $entity->getStatements();

		if ( empty( $this->statementGuid ) ) {
			$this->addStatement( $statements, $entity->getId(), $summary );
		} else {
			$this->setStatement( $statements, $summary );
		}
	}

	/**
	 * @param StatementList $statements
	 * @param EntityId $entityId
	 * @param Summary|null $summary
	 */
	private function addStatement( StatementList $statements, EntityId $entityId, Summary $summary = null ) {
		$this->statementGuid = $this->guidGenerator->newGuid( $entityId );
		$statements->addNewStatement( $this->snak, null, null, $this->statementGuid );
		$this->updateSummary( $summary, 'create', '', $this->getClaimSummaryArgs( $this->snak ) );
	}

	/**
	 * @param StatementList $statements
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	private function setStatement( StatementList $statements, Summary $summary = null ) {
		$statement = $statements->getFirstStatementWithGuid( $this->statementGuid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have a statement with GUID " . $this->statementGuid );
		}

		$propertyId = $statement->getMainSnak()->getPropertyId();

		if ( !$propertyId->equals( $this->snak->getPropertyId() ) ) {
			throw new ChangeOpException( "Claim with GUID "
				. $this->statementGuid . " uses property "
				. $propertyId . ", can't change to "
				. $this->snak->getPropertyId() );
		}

		$statement->setMainSnak( $this->snak );
		$this->updateSummary( $summary, null, '', $this->getClaimSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $mainSnak
	 *
	 * @return array
	 */
	protected function getClaimSummaryArgs( Snak $mainSnak ) {
		$propertyId = $mainSnak->getPropertyId();
		return array( array( $propertyId->getSerialization() => $mainSnak ) );
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		return $this->snakValidator->validate( $this->snak );
	}

}
