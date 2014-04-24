<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\SiteLinkLookup;

/**
 * Factory for ChangeOps that merge Items.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MergeChangeOpsFactory {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $termDuplicateDetector;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param ChangeOpFactoryProvider $factoryProvider
	 */
	public function __construct(
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		SiteLinkLookup $siteLinkLookup,
		ChangeOpFactoryProvider $factoryProvider
	) {
		$this->termDuplicateDetector = $termDuplicateDetector;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->factoryProvider = $factoryProvider;
	}

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param array $ignoreConflicts list of elements to ignore conflicts for
	 *   can only contain 'label' and or 'description' and or 'sitelink'
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOpsMerge
	 *
	 * @todo: Injecting ChangeOpFactoryProvider is an Abomination Unto Nuggan, we'll
	 *        need a ItemMergeChangeOpsSequenceBuilder or some such.
	 */
	public function newMergeOps(
		Item $fromItem,
		Item $toItem,
		$ignoreConflicts = array()
	) {
		return new ChangeOpsMerge(
			$fromItem,
			$toItem,
			$ignoreConflicts,
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this->factoryProvider
		);
	}
}
