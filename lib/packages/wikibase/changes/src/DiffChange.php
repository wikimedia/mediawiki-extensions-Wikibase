<?php

namespace Wikibase\Lib\Changes;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class DiffChange extends ChangeRow {

	/**
	 * @return EntityDiffChangedAspects
	 */
	public function getCompactDiff() {
		$info = $this->getInfo();

		if ( !array_key_exists( ChangeRow::COMPACT_DIFF, $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			$this->logger->warning( 'Cannot get the diff when it has not been set yet.' );
			return ( new EntityDiffChangedAspectsFactory( $this->logger ) )->newEmpty();
		} else {
			return $info[ChangeRow::COMPACT_DIFF];
		}
	}

	public function setCompactDiff( EntityDiffChangedAspects $diff ) {
		$info = $this->getInfo();
		$info[ChangeRow::COMPACT_DIFF] = $diff;
		$this->setField( ChangeRow::INFO, $info );
	}

}
