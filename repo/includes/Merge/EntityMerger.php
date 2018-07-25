<?php

namespace Wikibase\Repo\Merge;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for objects that merge entities or parts of entities
 */
interface EntityMerger {

	/**
	 * Performs the merge by modifying $source and $target by reference
	 *
	 * @param EntityDocument $source
	 * @param EntityDocument $target
	 *
	 * @return void
	 */
	public function merge( EntityDocument $source, EntityDocument $target );

}
