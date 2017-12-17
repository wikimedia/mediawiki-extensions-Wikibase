<?php

namespace Wikibase;

use Wikibase\Lib\Changes\EntityDiffChangedAspects;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class DiffChange extends ChangeRow {

	/**
	 * @return EntityDiffChangedAspects
	 */
	public function getCompactDiff() {
		$info = $this->getInfo();

		if ( !array_key_exists( 'compactDiff', $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			wfLogWarning( 'Cannot get the diff when it has not been set yet.' );
			return EntityDiffChangedAspects::newEmpty();
		} else {
			return $info['compactDiff'];
		}
	}

	public function setCompactDiff( EntityDiffChangedAspects $diff ) {
		$info = $this->getInfo();
		$info['compactDiff'] = $diff;
		$this->setField( 'info', $info );
	}

}
