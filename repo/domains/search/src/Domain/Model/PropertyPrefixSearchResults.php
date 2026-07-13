<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchResults extends ArrayIterator {

	private bool $hasMore = false;

	public function __construct( PropertyPrefixSearchResult ...$results ) {
		parent::__construct( array_values( $results ) );
	}

	public static function withHasMore( bool $hasMore, PropertyPrefixSearchResult ...$results ): self {
		$instance = new self( ...$results );
		$instance->hasMore = $hasMore;
		return $instance;
	}

	public function hasMore(): bool {
		return $this->hasMore;
	}

}
