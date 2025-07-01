<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class RestfulSearchNotAvailableRouteHandler extends Handler {

	public function execute(): Response {
		$responseFactory = new ResponseFactory();

		return $responseFactory->newErrorResponse(
			500,
			'search-not-available',
			'RESTful Search is not available due to insufficient server configuration'
		);
	}
}
