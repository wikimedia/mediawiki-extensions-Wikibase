<?php

namespace Wikibase\Client\Changes;

use Wikibase\Change;

/**
 * Interface for service objects implementing some transformation on a list of Change objects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
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