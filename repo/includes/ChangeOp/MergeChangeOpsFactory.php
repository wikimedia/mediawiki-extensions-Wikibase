<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Validators\EntityConstraintProvider;

/**
 * Factory for ChangeOps that merge Items.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MergeChangeOpsFactory {

	/**
	 * @var EntityConstraintProvider
	 */
	private $constraintProvider;

	/**
	 * @var ChangeOpFactoryProvider
	 */
	private $factoryProvider;

	/**
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ChangeOpFactoryProvider $factoryProvider
	 */
	public function __construct(
		EntityConstraintProvider $constraintProvider,
		ChangeOpFactoryProvider $factoryProvider
	) {
		$this->constraintProvider = $constraintProvider;
		$this->factoryProvider = $factoryProvider;
	}

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param string[] $ignoreConflicts list of elements to ignore conflicts for
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
			$this->constraintProvider,
			$this->factoryProvider
		);
	}

}
