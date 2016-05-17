<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * Factory for ChangeOps that modify Statements.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class StatementChangeOpFactory {

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
	 * @var SnakValidator
	 */
	private $referenceSnakValidator;

	public function __construct(
		GuidGenerator $guidGenerator,
		StatementGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		SnakValidator $snakValidator,
		SnakValidator $referenceSnakValidator
	) {
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->snakValidator = $snakValidator;
		$this->referenceSnakValidator = $referenceSnakValidator;
	}

	/**
	 * @param Statement $statement
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetStatementOp( Statement $statement, $index = null ) {
		return new ChangeOpStatement(
			$statement,
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator,
			$index
		);
	}

	/**
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveStatementOp( $guid ) {
		return new ChangeOpRemoveStatement( $guid );
	}

	/**
	 * @param string $statementGuid
	 * @param Snak $snak
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetMainSnakOp( $statementGuid, Snak $snak ) {
		return new ChangeOpMainSnak( $statementGuid, $snak, $this->guidGenerator, $this->snakValidator );
	}

	/**
	 * @param string $statementGuid
	 * @param Snak $snak
	 * @param string $snakHash (if not empty '', the old snak is replaced)
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetQualifierOp( $statementGuid, Snak $snak, $snakHash ) {
		//XXX: index??
		return new ChangeOpQualifier( $statementGuid, $snak, $snakHash, $this->snakValidator );
	}

	/**
	 * @param string $statementGuid
	 * @param string $snakHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveQualifierOp( $statementGuid, $snakHash ) {
		return new ChangeOpQualifierRemove( $statementGuid, $snakHash );
	}

	/**
	 * @param string $statementGuid
	 * @param Reference $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param int|null $index Indicates the new desired position in the list of references.
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetReferenceOp( $statementGuid, Reference $reference, $referenceHash, $index = null ) {
		return new ChangeOpReference( $statementGuid, $reference, $referenceHash, $this->referenceSnakValidator, $index );
	}

	/**
	 * @param string $statementGuid
	 * @param string $referenceHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveReferenceOp( $statementGuid, $referenceHash ) {
		return new ChangeOpReferenceRemove( $statementGuid, $referenceHash );
	}

	/**
	 * @param string $statementGuid
	 * @param int $rank
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetStatementRankOp( $statementGuid, $rank ) {
		return new ChangeOpStatementRank( $statementGuid, $rank );
	}

}
