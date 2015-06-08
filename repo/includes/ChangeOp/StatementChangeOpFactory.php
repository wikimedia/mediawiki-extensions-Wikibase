<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Reference;
use Wikibase\Validators\SnakValidator;

/**
 * Factory for ChangeOps that modify Statements.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class StatementChangeOpFactory {

	/**
	 * @var SnakValidator
	 */
	private $referenceSnakValidator;

	public function __construct( SnakValidator $referenceSnakValidator ) {
		$this->referenceSnakValidator = $referenceSnakValidator;
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
