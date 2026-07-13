<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

/**
 * Transitional marker for controllers that honour {@link WbSearchEntitiesRequest::$offset}
 * and return a {@link WbSearchEntitiesResponse} (results + hasMore). Once every
 * implementer has migrated, search() is narrowed to WbSearchEntitiesResponse and
 * this marker is removed (T428038).
 *
 * @license GPL-2.0-or-later
 */
interface PaginatingWbSearchEntitiesController extends WbSearchEntitiesController {
}
