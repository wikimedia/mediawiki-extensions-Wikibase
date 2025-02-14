<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchResults extends ArrayIterator {

	public function __construct( ItemSearchResult ...$results ) {
		parent::__construct( $results );
	}

}
