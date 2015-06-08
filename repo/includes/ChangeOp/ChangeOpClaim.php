<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\Result;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
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
		$index = null
	) {
		if ( !is_int( $index ) && $index !== null ) {
			throw new InvalidArgumentException( '$index must be an integer or null' );
		}

		$this->claim = $claim;
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->snakValidator = $snakValidator;
		$this->index = $index;
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
		} elseif ( !$entity->getId()->equals( $guid->getEntityId() ) ) {
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

		$statements = $this->removeStatement( $entity->getStatements()->toArray(), $summary );
		$statements = $this->addStatement( $statements );
		$this->setStatements( $entity, $statements );
	}

	/**
	 * @param Statement[] $statements
	 * @param Summary|null $summary
	 *
	 * @return Statement[]
	 */
	private function removeStatement( array $statements, Summary $summary = null ) {
		$guid = $this->claim->getGuid();
		$newStatements = array();
		$oldStatement = null;

		foreach ( $statements as $statement ) {
			if ( $statement->getGuid() === $guid && $oldStatement === null ) {
				$oldStatement = $statement;

				if ( $this->index === null ) {
					$this->index = count( $newStatements );
				}
			} else {
				$newStatements[] = $statement;
			}
		}

		if ( $oldStatement === null ) {
			$this->updateSummary( $summary, 'create' );
		} else {
			$this->checkMainSnakUpdate( $oldStatement );
			$this->updateSummary( $summary, 'update' );
		}

		return $newStatements;
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
	 * @param Statement[] $statements
	 *
	 * @throws ChangeOpException
	 * @return Statement[]
	 */
	private function addStatement( array $statements ) {
		// If we fail with the user supplied index and the index is greater than or equal 0
		// presume the user wants to have the index at the end of the list.
		if ( $this->index < 0 ) {
			throw new ChangeOpException( 'Can not add claim at given index: '. $this->index );
		}

		$indexedStatements = new ByPropertyIdArray( $statements );
		$indexedStatements->buildIndex();

		try {
			$indexedStatements->addObjectAtIndex( $this->claim, $this->index );
			$statements = $indexedStatements->toFlatArray();
		} catch ( OutOfBoundsException $ex ) {
			$statements[] = $this->claim;
		}

		return $statements;
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
