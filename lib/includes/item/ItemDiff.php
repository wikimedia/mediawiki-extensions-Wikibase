<?php

namespace Wikibase;
use Diff\Diff;

class ItemDiff extends EntityDiff {

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		return isset( $this['links'] ) ? $this['links'] : new Diff( array(), true );
	}

}