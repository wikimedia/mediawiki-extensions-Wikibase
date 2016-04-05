<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\Result;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Summary;

/**
 * Class for statement modification operations
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpStatement extends ChangeOpBase {

	/**
	 * @var Statement
	 */
	private $statement;

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @var StatementGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var StatementGuidParser
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
	 * @param Statement $statement
	 * @param GuidGenerator $guidGenerator
	 * @param StatementGuidValidator $guidValidator
	 * @param StatementGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 * @param int|null $index Where the claim should be placed among the other claims.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		Statement $statement,
		GuidGenerator $guidGenerator,
		StatementGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		SnakValidator $snakValidator,
		$index = null
	) {
		if ( !is_int( $index ) && $index !== null ) {
			throw new InvalidArgumentException( '$index must be an integer or null' );
		}

		$this->statement = $statement;
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->snakValidator = $snakValidator;
		$this->index = $index;
	}

	/**
	 * @see ChangeOp::apply
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListHolder ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListHolder' );
		}

		if ( $this->statement->getGuid() === null ) {
			$this->statement->setGuid( $this->guidGenerator->newGuid( $entity->getId() ) );
		}

		$guid = $this->guidParser->parse( $this->statement->getGuid() );

		if ( $this->guidValidator->validate( $guid->getSerialization() ) === false ) {
			throw new ChangeOpException( "Claim does not have a valid GUID" );
		} elseif ( !$entity->getId()->equals( $guid->getEntityId() ) ) {
			throw new ChangeOpException( "Claim GUID invalid for given entity" );
		}

		$this->applyStatementToEntity( $entity, $summary );
	}

	/**
	 * @param StatementListHolder $statementListHolder
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	private function applyStatementToEntity( StatementListHolder $statementListHolder, Summary $summary = null ) {
		$statements = $this->removeStatement( $statementListHolder->getStatements()->toArray(), $summary );
		$statements = $this->addStatement( $statements );
		$statementListHolder->setStatements( new StatementList( $statements ) );
	}

	/**
	 * @param Statement[] $statements
	 * @param Summary|null $summary
	 *
	 * @return Statement[]
	 */
	private function removeStatement( array $statements, Summary $summary = null ) {
		$guid = $this->statement->getGuid();
		$newStatements = [];
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
		$newMainSnak = $this->statement->getMainSnak();
		$oldPropertyId = $oldStatement->getMainSnak()->getPropertyId();

		if ( !$oldPropertyId->equals( $newMainSnak->getPropertyId() ) ) {
			$guid = $this->statement->getGuid();
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
			$indexedStatements->addObjectAtIndex( $this->statement, $this->index );
			$statements = $indexedStatements->toFlatArray();
		} catch ( OutOfBoundsException $ex ) {
			$statements[] = $this->statement;
		}

		return $statements;
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		return $this->snakValidator->validateClaimSnaks( $this->statement );
	}

}
