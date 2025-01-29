<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class Qualifiers extends ArrayIterator {

	public function __construct( PropertyValuePair ...$qualifiers ) {
		parent::__construct( $qualifiers );
	}

}
