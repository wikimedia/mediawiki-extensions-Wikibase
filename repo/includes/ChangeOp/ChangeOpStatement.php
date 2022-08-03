<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\Result;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * Class for statement modification operations
 *
 * @license GPL-2.0-or-later
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
	 * @param int|null $index Where the statement should be placed among the other statements.
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
		if ( $index !== null && ( !is_int( $index ) || $index < 0 ) ) {
			throw new InvalidArgumentException( '$index must be an non-negative integer or null' );
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
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$entityId = $entity->getId();

		if ( $this->statement->getGuid() === null ) {
			$this->statement->setGuid( $this->guidGenerator->newGuid( $entityId ) );
		}

		$this->validateStatementGuid( $entityId );

		$entityStatements = $entity->getStatements();
		$oldIndex = $this->removeStatement( $entityStatements );

		if ( $this->index !== null ) {
			$statements = $this->addStatementToGroup( $entityStatements, $this->index );
			$entityStatements->clear();
			foreach ( $statements as $statement ) {
				$entityStatements->addStatement( $statement );
			}
		} else {
			$entityStatements->addStatement( $this->statement, $oldIndex );
		}

		$this->updateSummary( $summary, $oldIndex === null ? 'create' : 'update' );

		return new GenericChangeOpResult( $entityId, true );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws ChangeOpException
	 */
	private function validateStatementGuid( EntityId $entityId ) {
		try {
			$guid = $this->guidParser->parse( $this->statement->getGuid() );
		} catch ( StatementGuidParsingException $ex ) {
			throw new ChangeOpException( 'Statement GUID can not be parsed' );
		}

		if ( !$this->guidValidator->validate( $guid->getSerialization() ) ) {
			throw new ChangeOpException( 'Statement does not have a valid GUID' );
		}

		if ( !$guid->getEntityId()->equals( $entityId ) ) {
			throw new ChangeOpException( 'Statement GUID invalid for given entity' );
		}
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return int|null
	 */
	private function removeStatement( StatementList $statements ) {
		$guid = $this->statement->getGuid();
		$oldIndex = null;
		$oldStatement = null;

		foreach ( $statements->toArray() as $index => $statement ) {
			if ( $statement->getGuid() === $guid ) {
				$oldIndex = $index;
				$oldStatement = $statement;
				$statements->removeStatementsWithGuid( $guid );
				break;
			}
		}

		if ( $oldStatement !== null ) {
			$this->checkMainSnakUpdate( $oldStatement );
		}

		return $oldIndex;
	}

	/**
	 * Checks that the update of the main snak is permissible.
	 *
	 * This checks that the main snaks of the old and the new statement
	 * refer to the same property.
	 *
	 * @param Statement $oldStatement
	 *
	 * @throws ChangeOpException If the main snak update is illegal.
	 */
	private function checkMainSnakUpdate( Statement $oldStatement ) {
		$newMainSnak = $this->statement->getMainSnak();
		$oldPropertyId = $oldStatement->getPropertyId();

		if ( !$oldPropertyId->equals( $newMainSnak->getPropertyId() ) ) {
			$guid = $this->statement->getGuid();
			throw new ChangeOpException( "Claim with GUID $guid uses property "
				. $oldPropertyId . ", can't change to "
				. $newMainSnak->getPropertyId() );
		}
	}

	/**
	 * @param StatementList $statements
	 * @param int $index
	 *
	 * @return Statement[]
	 */
	private function addStatementToGroup( StatementList $statements, $index ) {
		// If we fail with the user supplied index and the index is greater than or equal 0
		// presume the user wants to have the index at the end of the list.
		$indexedStatements = new ByPropertyIdArray( $statements->toArray() );
		$indexedStatements->buildIndex();

		try {
			$indexedStatements->addObjectAtIndex( $this->statement, $index );
		} catch ( OutOfBoundsException $ex ) {
			$statements->addStatement( $this->statement );
			return $statements->toArray();
		}

		return $indexedStatements->toFlatArray();
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		return $this->snakValidator->validateStatementSnaks( $this->statement );
	}

}
