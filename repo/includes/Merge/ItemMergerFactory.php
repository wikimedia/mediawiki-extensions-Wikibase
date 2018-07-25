<?php

namespace Wikibase\Repo\Merge;

use SiteLookup;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @license GPL-2.0-or-later
 */
class ItemMergerFactory {

	/**
	 * @var EntityConstraintProvider
	 */
	private $constraintProvider;

	/**
	 * @var ChangeOpFactoryProvider
	 */
	private $changeOpFactoryProvider;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	public function __construct(
		EntityConstraintProvider $constraintProvider,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		SiteLookup $siteLookup
	) {
		$this->constraintProvider = $constraintProvider;
		$this->changeOpFactoryProvider = $changeOpFactoryProvider;
		$this->siteLookup = $siteLookup;
	}

	public function newItemMerger( array $ignoreConflicts ) {
		return new ItemMerger(
			$ignoreConflicts,
			$this->constraintProvider,
			$this->changeOpFactoryProvider,
			$this->siteLookup
		);
	}

}
