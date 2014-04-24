<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\SiteLinkLookup;
use Wikibase\Validators\SnakValidator;

/**
 * Factory for ChangeOps.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ChangeOpFactory {

	/**
	 * @var string
	 */
	protected $entityType;

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	protected $termDuplicateDetector;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var ClaimGuidGenerator
	 */
	protected $guidGenerator;

	/**
	 * @var ClaimGuidValidator
	 */
	protected $guidValidator;

	/**
	 * @var ClaimGuidParser
	 */
	protected $guidParser;

	/**
	 * @var SnakValidator
	 */
	protected $snakValidator;

	/**
	 * @param string $entityType
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 */
	public function __construct(
		$entityType,
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		SiteLinkLookup $siteLinkLookup,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator
	) {
		$this->entityType = $entityType;

		$this->termDuplicateDetector = $termDuplicateDetector;
		$this->siteLinkLookup = $siteLinkLookup;

		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;

		$this->snakValidator = $snakValidator;
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'add' );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'set' );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @return ChangeOp
	 */
	public function newRemoveAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'remove' );
	}

	/**
	 * @param string $language
	 * @param string $description
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetDescriptionOp( $language, $description ) {
		return new ChangeOpDescription( $language, $description );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveDescriptionOp( $language ) {
		return new ChangeOpDescription( $language, null );
	}

	/**
	 * @param string $language
	 * @param string $label
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetLabelOp( $language, $label ) {
		return new ChangeOpLabel( $language, $label );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveLabelOp( $language ) {
		return new ChangeOpLabel( $language, null );
	}

	/**
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddClaimOp( Claim $claim, $index = null ) {
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
	 * @param string $claimGuid
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveClaimOp( $claimGuid ) {
		return new ChangeOpClaimRemove( $claimGuid );
	}

	/**
	 * @param string $claimGuid
	 * @param Snak $snak
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetMainSnakOp( $claimGuid, Snak $snak ) {
		return new ChangeOpMainSnak( $claimGuid, $snak, $this->guidGenerator, $this->snakValidator );
	}

	/**
	 * @param string $claimGuid
	 * @param Snak $snak
	 * @param string $snakHash (if not empty '', the old snak is replaced)
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetQualifierOp( $claimGuid, Snak $snak, $snakHash ) {
		//XXX: index??
		return new ChangeOpQualifier( $claimGuid, $snak, $snakHash, $this->snakValidator );
	}

	/**
	 * @param string $claimGuid
	 * @param string $snakHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveQualifierOp( $claimGuid, $snakHash ) {
		return new ChangeOpQualifierRemove( $claimGuid, $snakHash );
	}

}
