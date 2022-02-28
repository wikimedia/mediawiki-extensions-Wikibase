<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\ResponseInterface;

/**
 * @license GPL-2.0-or-later
 */
class ApiNotEnabledRouteHandler extends Handler {

	public function execute(): ResponseInterface {
		return $this->getResponseFactory()->createHttpError(
			403,
			[ 'error' => 'The Wikibase REST API is not enabled on this wiki' ]
		);
	}

}
