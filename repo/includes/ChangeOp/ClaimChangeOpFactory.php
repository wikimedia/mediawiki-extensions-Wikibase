<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Validators\SnakValidator;

/**
 * Factory for ChangeOps that modify Claims.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ClaimChangeOpFactory {

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
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 */
	public function __construct(
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator
	) {
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;

		$this->snakValidator = $snakValidator;
	}

	/**
	 * @param Statement $statement
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddClaimOp( Statement $statement, $index = null ) {
		return new ChangeOpClaim(
			$statement,
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator,
			$index
		);
	}

	/**
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetClaimOp( Claim $claim, $index = null ) {
		return new ChangeOpClaim(
			$claim,
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

}
