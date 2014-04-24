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
	 * @param string $entityType The type of entity to provide a ChangeOpFactory for.
	 *        Used e.g. to determine the validation rules to be applied by each ChangeOp.
	 *
	 * @return FingerprintChangeOpFactory
	 */
	public function getFingerprintChangeOpFactory( $entityType ) {
		//@todo: inject validators
		return new FingerprintChangeOpFactory();
	}

	/**
	 * @param string $entityType The type of entity to provide a ChangeOpFactory for.
	 *        Used e.g. to determine the validation rules to be applied by each ChangeOp.
	 *
	 * @return ClaimChangeOpFactory
	 */
	public function getClaimChangeOpFactory( $entityType ) {
		return new ClaimChangeOpFactory(
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator
		);
	}

	/**
	 * @param string $entityType The type of entity to provide a ChangeOpFactory for.
	 *        User e.g. to determine the validation rules to be applied by each ChangeOp.
	 *
	 * @return StatementChangeOpFactory
	 */
	public function getStatementChangeOpFactory( $entityType ) {
		return new StatementChangeOpFactory( $this->snakValidator );
	}

	/**
	 * @param string $entityType The type of entity to provide a ChangeOpFactory for.
	 *        User e.g. to determine the validation rules to be applied by each ChangeOp.
	 *
	 * @return SiteLinkChangeOpFactory
	 */
	public function getSiteLinkChangeOpFactory( $entityType ) {
		//@todo: inject validators
		return new SiteLinkChangeOpFactory();
	}

	/**
	 * @param string $entityType The type of entity to provide a ChangeOpFactory for.
	 *        User e.g. to determine the validation rules to be applied by each ChangeOp.
	 *
	 * @throws \InvalidArgumentException
	 * @return MergeItemsChangeOpsFactory
	 */
	public function getMergeChangeOpFactory( $entityType ) {
		if ( $entityType !== Item::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Can not merge ' . $entityType . ' entities.' );
		}

		return new MergeItemsChangeOpsFactory(
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this
		);
	}
}
