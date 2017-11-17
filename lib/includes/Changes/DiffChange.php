<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class DiffChange extends ChangeRow {

	/**
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return EntityDiffChangedAspects
	 */
	public function getDiff( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( !array_key_exists( 'diff', $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			wfLogWarning( 'Cannot get the diff when it has not been set yet.' );
			return ( new EntityDiffChangedAspectsFactory() )->newFromEntityDiff( new Diff() );
		} else {
			return $info['diff'];
		}
	}

	public function setDiff( EntityDiffChangedAspects $diff ) {
		$info = $this->getInfo();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

}
