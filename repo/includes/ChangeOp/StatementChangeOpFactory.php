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
	 * @param string $claimGuid
	 * @param Reference $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param int|null $index Indicates the new desired position in the list of references.
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetReferenceOp( $claimGuid, Reference $reference, $referenceHash, $index = null ) {
		return new ChangeOpReference( $claimGuid, $reference, $referenceHash, $this->referenceSnakValidator, $index );
	}

	/**
	 * @param string $claimGuid
	 * @param string $referenceHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveReferenceOp( $claimGuid, $referenceHash ) {
		return new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
	}

	/**
	 * @param string $claimGuid
	 * @param int $rank
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetStatementRankOp( $claimGuid, $rank ) {
		return new ChangeOpStatementRank( $claimGuid, $rank );
	}

}
