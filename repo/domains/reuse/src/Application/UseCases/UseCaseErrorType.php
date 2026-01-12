<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
enum UseCaseErrorType {
	case INVALID_SEARCH_QUERY;
	case INVALID_SEARCH_LIMIT;
	case INVALID_SEARCH_OFFSET;
}
