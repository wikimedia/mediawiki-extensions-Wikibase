<?php

namespace Wikibase\ChangeOp;

use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\SiteLinkLookup;
use Wikibase\Validators\SnakValidator;

/**
 * Provider for ChangeOpFactories.
 *
 * Yes, this is a factory factory. Sue me and call me Java.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ChangeOpFactoryProvider {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $termDuplicateDetector;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

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
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 */
	public function __construct(
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		SiteLinkLookup $siteLinkLookup,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator
	) {
		$this->termDuplicateDetector = $termDuplicateDetector;
		$this->siteLinkLookup = $siteLinkLookup;

		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;

		$this->snakValidator = $snakValidator;
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	public function getFingerprintChangeOpFactory() {
		//@todo: inject validators
		return new FingerprintChangeOpFactory();
	}

	/**
	 * @return ClaimChangeOpFactory
	 */
	public function getClaimChangeOpFactory() {
		return new ClaimChangeOpFactory(
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator
		);
	}

	/**
	 * @return StatementChangeOpFactory
	 */
	public function getStatementChangeOpFactory() {
		return new StatementChangeOpFactory( $this->snakValidator );
	}

	/**
	 * @return SiteLinkChangeOpFactory
	 */
	public function getSiteLinkChangeOpFactory() {
		//@todo: inject validators
		return new SiteLinkChangeOpFactory();
	}

	/**
	 * @return MergeChangeOpsFactory
	 */
	public function getMergeChangeOpFactory() {
		return new MergeChangeOpsFactory(
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this
		);
	}
}
