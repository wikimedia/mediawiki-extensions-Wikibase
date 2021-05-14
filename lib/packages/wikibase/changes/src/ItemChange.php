<?php

namespace Wikibase\Lib\Changes;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ItemChange extends EntityChange {

	public function getSiteLinkDiff(): Diff {
		$aspects = $this->getCompactDiff();
		if ( !( $aspects instanceof EntityDiffChangedAspects ) ) {
			$this->logWarning( $aspects );
			return new Diff();
		}
		return $this->getDiffFromSiteLinkChanges( $aspects->getSiteLinkChanges() );
	}

	/**
	 * @param array[] $siteLinkChanges
	 * @return Diff
	 */
	private function getDiffFromSiteLinkChanges( array $siteLinkChanges ): Diff {
		$siteLinkDiff = [];
		foreach ( $siteLinkChanges as $wiki => $change ) {
			if ( $change[0] === $change[1] ) {
				continue;
			}
			$siteLinkDiff[$wiki] = $this->getDiffFromSiteLinkChangesPerWiki( $change );
		}

		return new Diff( $siteLinkDiff, true );
	}

	private function getDiffFromSiteLinkChangesPerWiki( array $change ): Diff {
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
		$this->logger->warning( 'Cannot get sitelink diff from {class}. Change #{id}, type {type}', [
			'class' => $obj === null ? 'null' : get_class( $obj ),
			'id' => $this->getId(),
			'type' => $this->getType(),
		] );
	}

}
