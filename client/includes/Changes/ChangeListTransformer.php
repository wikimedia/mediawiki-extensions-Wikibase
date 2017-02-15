<?php

namespace Wikibase\Client\Changes;

use Wikibase\EntityChange;

/**
 * Interface for service objects implementing some transformation on a list of Change objects.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface ChangeListTransformer {

	/**
	 * Processes the given list of changes, possibly merging, filtering or otherwise modifying
	 * changes and/or the list of changes.
	 *
	 * @param EntityChange[] $changes
	 *
	 * @return EntityChange[]
	 */
	public function transformChangeList( array $changes );

}
