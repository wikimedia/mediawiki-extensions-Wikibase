<?php

namespace Wikibase\Repo\Tests\Rdf;

use InvalidArgumentException;
use Wikimedia\Purtle\BNodeLabeler;

/**
 * A BNodeLabeler that refuses to generate any labels,
 * used to assert that all labels are assigned deterministically.
 *
 * @license GPL-2.0-or-later
 */
class NoopBNodeLabeler extends BNodeLabeler {

	public function getLabel( $label = null ) {
		if ( $label === null ) {
			throw new InvalidArgumentException( 'Must explicitly assign blank node labels!' );
		}

		return $label;
	}

}
