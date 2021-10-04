<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\Item;

/**
 * A {@link SiteLinkConflictLookup} composing several others.
 * Each conflict lookup is tried in turn,
 * and as soon as one lookup returns a nonempty list of conflicts,
 * that list is returned without trying the remaining lookups.
 *
 * @license GPL-2.0-or-later
 */
class CompositeSiteLinkConflictLookup implements SiteLinkConflictLookup {

	/** @var SiteLinkConflictLookup[] */
	private $lookups;

	/** @param SiteLinkConflictLookup[] $lookups */
	public function __construct( array $lookups ) {
		$this->lookups = $lookups;
	}

	public function getConflictsForItem( Item $item, int $db = null ): array {
		foreach ( $this->lookups as $lookup ) {
			$conflicts = $lookup->getConflictsForItem( $item, $db );
			if ( $conflicts !== [] ) {
				return $conflicts;
			}
		}

		return [];
	}

}
