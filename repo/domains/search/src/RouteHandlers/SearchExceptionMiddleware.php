<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Handler\Helper\RestStatusTrait;
use MediaWiki\Rest\Response;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\RestApi\Middleware\Middleware;

/**
 * @license GPL-2.0-or-later
 */
class SearchExceptionMiddleware implements Middleware {
	use RestStatusTrait;

	public function run( Handler $routeHandler, callable $runNext ): Response {
		try {
			return $runNext();
		} catch ( EntitySearchException $searchException ) {
			$this->throwExceptionForStatus( $searchException->getStatus(), 'rest-search-error', 500 );
		}
	}
}
