<?php

namespace Wikibase\Repo\ChangeOp;

use SiteLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Provider for ChangeOpFactories.
 *
 * Yes, this is a factory factory. Sue me and call me Java.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeOpFactoryProvider {

	/**
	 * @var EntityConstraintProvider
	 */
	private $constraintProvider;

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
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/** @var SnakNormalizer */
	private $snakNormalizer;

	/** @var ReferenceNormalizer */
	private $referenceNormalizer;

	/** @var StatementNormalizer */
	private $statementNormalizer;

	/**
	 * @var string[]
	 */
	private $allowedBadgeItemIds;

	/** @var bool */
	private $normalize;

	/**
	 * @param EntityConstraintProvider $constraintProvider
	 * @param GuidGenerator $guidGenerator
	 * @param StatementGuidValidator $guidValidator
	 * @param StatementGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 * @param TermValidatorFactory $termValidatorFactory
	 * @param SiteLookup $siteLookup
	 * @param string[] $allowedBadgeItemIds
	 */
	public function __construct(
		EntityConstraintProvider $constraintProvider,
		GuidGenerator $guidGenerator,
		StatementGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		SnakValidator $snakValidator,
		TermValidatorFactory $termValidatorFactory,
		SiteLookup $siteLookup,
		SnakNormalizer $snakNormalizer,
		ReferenceNormalizer $referenceNormalizer,
		StatementNormalizer $statementNormalizer,
		array $allowedBadgeItemIds,
		bool $normalize
	) {
		$this->constraintProvider = $constraintProvider;

		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;

		$this->snakValidator = $snakValidator;
		$this->termValidatorFactory = $termValidatorFactory;

		$this->siteLookup = $siteLookup;

		$this->snakNormalizer = $snakNormalizer;
		$this->referenceNormalizer = $referenceNormalizer;
		$this->statementNormalizer = $statementNormalizer;

		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
		$this->normalize = $normalize;
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
			$this->snakValidator,
			$this->snakNormalizer,
			$this->referenceNormalizer,
			$this->statementNormalizer,
			$this->normalize
		);
	}

	/**
	 * @return SiteLinkChangeOpFactory
	 */
	public function getSiteLinkChangeOpFactory() {
		//@todo: inject validators instead of hardcoding checks in the ChangeOp.
		return new SiteLinkChangeOpFactory( $this->allowedBadgeItemIds );
	}

	/**
	 * @return MergeFactory
	 */
	public function getMergeFactory() {
		return new MergeFactory(
			$this->constraintProvider,
			$this,
			$this->siteLookup
		);
	}

}
