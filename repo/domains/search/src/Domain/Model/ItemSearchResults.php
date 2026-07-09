<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchResults extends ArrayIterator {

	private bool $hasMore = false;

	public function __construct( ItemSearchResult ...$results ) {
		parent::__construct( array_values( $results ) );
	}

	public static function withHasMore( bool $hasMore, ItemSearchResult ...$results ): self {
		$instance = new self( ...$results );
		$instance->hasMore = $hasMore;
		return $instance;
	}

	public function hasMore(): bool {
		return $this->hasMore;
	}

}
