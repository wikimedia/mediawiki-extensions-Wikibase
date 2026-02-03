<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

/**
 * @license GPL-2.0-or-later
 */
enum GraphQLErrorType {
	case ITEM_NOT_FOUND;
	case INVALID_SEARCH_CURSOR;
	case INVALID_SEARCH_LIMIT;
	case INVALID_SEARCH_QUERY;
	case SEARCH_NOT_AVAILABLE;
}
