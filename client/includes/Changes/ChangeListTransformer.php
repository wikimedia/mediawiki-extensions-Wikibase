<?php

namespace Wikibase\Client\Changes;

use Wikibase\Change;

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
	 * @param Change[] $changes
	 *
	 * @return Change[]
	 */
	public function transformChangeList( array $changes );

}
