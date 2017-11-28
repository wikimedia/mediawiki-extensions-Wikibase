<?php

namespace Wikibase;

use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOpRemove;
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
		$info = $this->getInfo();
		if ( !array_key_exists( 'diff', $info ) ) {
			if ( !array_key_exists( 'compactDiff', $info ) ) {
				$this->logWarning( $this );
				return new Diff();
			}

			$aspects = $info['compactDiff'];
			if ( !( $aspects instanceof EntityDiffChangedAspects ) ) {
				$this->logWarning( $aspects );
				return new Diff();
			}
			return $this->getDiffFromSiteLinkChanges( $aspects->getSiteLinkChanges() );

		}
		$diff = $this->getDiff();
		if ( !( $diff instanceof ItemDiff ) ) {
			$this->logWarning( $diff );
			return new Diff();
		} else {
			return $diff->getSiteLinkDiff();
		}
	}

	private function getDiffFromSiteLinkChanges( array $siteLinkChanges ) {
		$siteLinkDiff = [];
		foreach ( $siteLinkChanges as $wiki => $change ) {
			if ( $change[0] === $change[1] ) {
				continue;
			}
			$siteLinkDiff[$wiki] = $this->getDiffFromSiteLinkChangesPerWiki( $change );
		}

		return new Diff( $siteLinkDiff, true );
	}

	private function getDiffFromSiteLinkChangesPerWiki( array $change ) {
		if ( $change[0] === null && $change[1] !== null ) {
			return new Diff( [ 'name' => new DiffOpAdd( $change[1] ) ], true );
		} elseif ( $change[0] !== null && $change[1] === null ) {
			return new Diff( [ 'name' => new DiffOpRemove( $change[0] ) ], true );
		} else {
			return new Diff( [ 'name' => new DiffOpChange( $change[0], $change[1] ) ], true );
		}
	}

	private function logWarning( $obj ) {
		// This shouldn't happen, but we should be robust against corrupt, incomplete
		// or obsolete instances in the database, etc.

		$cls = $obj === null ? 'null' : get_class( $obj );
		wfLogWarning(
			'Cannot get sitelink diff from ' . $cls . '. Change #' . $this->getId()
			. ", type " . $this->getType() );
	}

}
