<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class DiffChange extends ChangeRow {

	/**
	 * @since 0.1
	 *
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return Diff
	 */
	public function getDiff( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( !array_key_exists( 'diff', $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			wfLogWarning( 'Cannot get the diff when it has not been set yet.' );
			return new Diff();
		} else {
			return $info['diff'];
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param Diff $diff
	 */
	public function setDiff( Diff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

}
