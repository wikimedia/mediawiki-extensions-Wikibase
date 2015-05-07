<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Summary;
use Wikibase\Validators\SnakValidator;

/**
 * Class for claim modification operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Mättig
 */
class ChangeOpClaim extends ChangeOpBase {

	/**
	 * @var Claim
	 */
	private $claim;

	/**
	 * @var ClaimGuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @var ClaimGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var ClaimGuidParser
	 */
	private $guidParser;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * @var int|null
	 */
	private $index;

	/**
	 * @var int|null
	 */
	private $groupIndex;

	/**
	 * @param Claim $claim
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 * @param int|null $index Where the claim should be placed among the other claims.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		Claim $claim,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator,
		$index = null,
		$groupIndex = null
	) {
		$this->assertIsIndex( $index );
		$this->assertIsIndex( $groupIndex );

		$this->claim = $claim;
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->snakValidator = $snakValidator;
		$this->index = $index;
		$this->groupIndex = $groupIndex;
	}

	private function assertIsIndex( $index ) {
		if (  ( !is_int( $index ) || $index < 0 ) && $index !== null ) {
			throw new InvalidArgumentException( '$index must be a non-negative integer or null' );
		}
	}

	/**
	 * @see ChangeOp::apply
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 * @return bool
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( $this->claim->getGuid() === null ){
			$this->claim->setGuid( $this->guidGenerator->newGuid( $entity->getId() ) );
		}

		$guid = $this->guidParser->parse( $this->claim->getGuid() );

		if ( $this->guidValidator->validate( $guid->getSerialization() ) === false ) {
			throw new ChangeOpException( "Claim does not have a valid GUID" );
		} else if ( !$entity->getId()->equals( $guid->getEntityId() ) ){
			throw new ChangeOpException( "Claim GUID invalid for given entity" );
		}

		$this->applyClaimToEntity( $entity, $summary );

		return true;
	}

	/**
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	private function applyClaimToEntity( Entity $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$statements = $entity->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $this->claim->getGuid() );

		$byPropertyIdMap = new ByPropertyIdMap( $statements );

		if ( $statement === null ) {
			$this->updateSummary( $summary, 'create' );
			$byPropertyIdMap->addElementAtIndex( $this->claim, $this->index );
		} else {
			$this->checkMainSnakUpdate( $statement );
			$this->updateSummary( $summary, 'update' );
			$byPropertyIdMap->moveElementToIndex( $this->claim, $this->index );
		}

		$byPropertyIdMap->moveGroupToIndex( $this->claim->getPropertyId(), $this->groupIndex );

		$statements->setStatements( $byPropertyIdMap->getflatArray() );
	}

	/**
	 * Checks that the update of the main snak is permissible.
	 *
	 * This checks that the main snaks of the old and the new claim
	 * refer to the same property.
	 *
	 * @param Statement $oldStatement
	 *
	 * @throws ChangeOpException If the main snak update is illegal.
	 */
	private function checkMainSnakUpdate( Statement $oldStatement ) {
		$newMainSnak = $this->claim->getMainSnak();
		$oldPropertyId = $oldStatement->getMainSnak()->getPropertyId();

		if ( !$oldPropertyId->equals( $newMainSnak->getPropertyId() ) ) {
			$guid = $this->claim->getGuid();
			throw new ChangeOpException( "Claim with GUID $guid uses property "
				. $oldPropertyId . ", can't change to "
				. $newMainSnak->getPropertyId() );
		}
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
		return $this->snakValidator->validateClaimSnaks( $this->claim );
	}

}
