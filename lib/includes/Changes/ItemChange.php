<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\ItemDiff;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ItemChange extends EntityChange {

	/**
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		$diff = $this->getDiff();

		if ( !( $diff instanceof ItemDiff ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// or obsolete instances in the database, etc.

			$cls = $diff === null ? 'null' : get_class( $diff );

			wfLogWarning( 'Cannot get sitelink diff from ' . $cls . '. Change #' . $this->getId() );

			return new Diff();
		} else {
			return $diff->getSiteLinkDiff();
		}
	}

}
