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
	 * Returns a ChangeOpFactory suitable for generating ChangeOps for the
	 * given type of entity.
	 *
	 * @param $entityType
	 *
	 * @return ChangeOpFactory
	 */
	public function getChangeOpFactory( $entityType ) {
		// @todo: validate $entityType against some list of known types.

		if ( $entityType === Item::ENTITY_TYPE ) {
			return $this->getItemChangeOpFactory();
		}

		return new ChangeOpFactory(
			$entityType,
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator
		);
	}

	/**
	 * Returns a ChangeOpFactory suitable for generating ChangeOps for Items.
	 *
	 * @return ItemChangeOpFactory
	 */
	public function getItemChangeOpFactory() {

		return new ItemChangeOpFactory(
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this->guidGenerator,
			$this->guidValidator,
			$this->guidParser,
			$this->snakValidator
		);
	}
}
