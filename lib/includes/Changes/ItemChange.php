<?php

namespace Wikibase;

use Diff\DiffOpAdd;
use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;

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
		if ( !array_key_exists( 'diff', $this->getInfo() ) ) {
			if ( !array_key_exists( 'compactDiff', $this->getInfo() ) ) {
				return $this->sendEmptyDiff( $this );
			}

			$aspects = $this->getAspectsDiff();
			if ( !( $aspects instanceof EntityDiffChangedAspects ) ) {
				return $this->sendEmptyDiff( $aspects );
			}
			return $this->getDiffFromSiteLinkChanges( $aspects->getSiteLinkChanges() );

		}
		$diff = $this->getDiff();
		if ( !( $diff instanceof ItemDiff ) ) {
			return $this->sendEmptyDiff( $diff );
		} else {
			return $diff->getSiteLinkDiff();
		}
	}

	private function getDiffFromSiteLinkChanges( array $siteLinkChanges ) {
		return new Diff(
			[ new DiffOpAdd( [ 'foowiki' => 'X', 'barwiki' => 'Y' ] ) ]
		);
	}

	private function sendEmptyDiff( $obj ) {
		// This shouldn't happen, but we should be robust against corrupt, incomplete
		// or obsolete instances in the database, etc.

		$cls = $obj === null ? 'null' : get_class( $obj );
		wfLogWarning(
			'Cannot get sitelink diff from ' . $cls . '. Change #' . $this->getId()
			. ", type " . $this->getType() );

		return new Diff();
	}
}
