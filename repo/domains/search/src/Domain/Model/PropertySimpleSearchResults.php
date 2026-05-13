<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class PropertySimpleSearchResults extends ArrayIterator {

	public function __construct( PropertySimpleSearchResult ...$results ) {
		parent::__construct( array_values( $results ) );
	}

}
