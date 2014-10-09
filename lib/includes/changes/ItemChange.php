<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;

/**
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ItemChange extends EntityChange {

	/**
	 * @since 0.3
	 *
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		$diff = $this->getDiff();

		if ( !$diff instanceof ItemDiff ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// or obsolete instances in the database, etc.

			$cls = $diff === null ? 'null' : get_class( $diff );

			wfLogWarning(
				'Cannot get sitelink diff from ' . $cls . '. Change #' . $this->getId()
				. ", type " . $this->getType() );

			return new Diff();
		} else {
			return $diff->getSiteLinkDiff();
		}
	}
}
