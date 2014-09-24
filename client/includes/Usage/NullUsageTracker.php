<?php

namespace Wikibase\Client\Usage;

use ArrayIterator;

/**
 * No-op implementation of the UsageTracker and UsageLookup interfaces.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NullUsageTracker implements UsageTracker, UsageLookup {

	public function trackUsedEntities( $pageId, array $usages ) {
		return array();
	}

	public function removeEntities( array $entities ) {
		// no-op
	}

	public function getUsageForPage( $pageId ) {
		return array();
	}

	public function getUnusedEntities( array $entities ) {
		return array();
	}

	public function getPagesUsing( array $entities, array $aspects = array() ) {
		return new ArrayIterator( array() );
	}
}
