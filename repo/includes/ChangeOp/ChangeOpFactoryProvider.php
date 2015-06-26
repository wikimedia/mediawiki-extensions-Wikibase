<?php

namespace Wikibase\ChangeOp;

use SiteLookup;
use Wikibase\DataModel\Statement\StatementGuidParser;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Validators\EntityConstraintProvider;
use Wikibase\Validators\SnakValidator;
use Wikibase\Validators\TermValidatorFactory;

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
	 * @var EntityConstraintProvider
	 */
	private $constraintProvider;

	/**
	 * @var ClaimGuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @var ClaimGuidValidator
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
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param StatementGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 * @param TermValidatorFactory $termValidatorFactory
	 * @param SiteLookup $siteLookup
	 */
	public function __construct(
		EntityConstraintProvider $constraintProvider,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		SnakValidator $snakValidator,
		TermValidatorFactory $termValidatorFactory,
		SiteLookup $siteLookup
	) {
		$this->constraintProvider = $constraintProvider;

		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;

		$this->snakValidator = $snakValidator;
		$this->termValidatorFactory = $termValidatorFactory;

		$this->siteLookup = $siteLookup;
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	public function getFingerprintChangeOpFactory() {
		return new FingerprintChangeOpFactory(
			$this->termValidatorFactory
		);
	}

	/**
	 * @return StatementChangeOpFactory
	 */
	public function getStatementChangeOpFactory() {
		return new StatementChangeOpFactory(
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator,
			$this->snakValidator
		);
	}

	/**
	 * @return SiteLinkChangeOpFactory
	 */
	public function getSiteLinkChangeOpFactory() {
		//@todo: inject validators instead of hardcoding checks in the ChangeOp.
		return new SiteLinkChangeOpFactory();
	}

	/**
	 * @return MergeChangeOpsFactory
	 */
	public function getMergeChangeOpFactory() {
		return new MergeChangeOpsFactory(
			$this->constraintProvider,
			$this,
			$this->siteLookup
		);
	}
}
