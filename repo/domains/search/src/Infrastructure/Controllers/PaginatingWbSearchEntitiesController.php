<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

/**
 * Transitional marker for controllers that honour {@link WbSearchEntitiesRequest::$offset}
 * and return a {@link WbSearchEntitiesResponse} (results + hasMore).
 *
 * @deprecated Unused since search() was narrowed to WbSearchEntitiesResponse; kept
 * only until EntitySchema and WikibaseCirrusSearch stop implementing it, then
 * removed (T428038).
 *
 * @license GPL-2.0-or-later
 */
interface PaginatingWbSearchEntitiesController extends WbSearchEntitiesController {
}
