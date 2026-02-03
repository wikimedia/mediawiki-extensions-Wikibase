<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLError extends Error {
	private function __construct( public readonly GraphQLErrorType $type, string $message ) {
		parent::__construct( $message );
	}

	public static function itemNotFound( string $itemId ): self {
		return new self(
			GraphQLErrorType::ITEM_NOT_FOUND,
			"Item \"$itemId\" does not exist.",
		);
	}

	public static function invalidSearchCursor(): self {
		return new self( GraphQLErrorType::INVALID_SEARCH_CURSOR, '"after" does not contain a valid cursor' );
	}

	public static function invalidSearchLimit(): self {
		return new self(
			GraphQLErrorType::INVALID_SEARCH_LIMIT,
			'"first" must not be less than 1 or greater than ' . FacetedItemSearchRequest::MAX_LIMIT
		);
	}

	public static function invalidSearchQuery( string $reason ): self {
		return new self( GraphQLErrorType::INVALID_SEARCH_QUERY, "Invalid search query: $reason" );
	}

	public static function searchNotAvailable(): self {
		return new self( GraphQLErrorType::SEARCH_NOT_AVAILABLE, 'Search is not available due to insufficient server configuration' );
	}

	public function isClientSafe(): bool {
		return true;
	}
}
