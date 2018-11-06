<?php

namespace Wikibase\DataModel\Services\Term;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Resolves property labels (which are unique per language) into entity IDs.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface PropertyLabelResolver {

	/**
	 * @param string[] $labels the labels
	 * @param string   $recache Flag, set to 'recache' to discard cached data and fetch fresh data
	 *                 from the database.
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' );

}
