<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * Middleware handling conditional requests with If-Match and If-Unmodified-Since headers.
 *
 * @license GPL-2.0-or-later
 */
class ModifiedPreconditionMiddleware implements Middleware {

	private $preconditionCheck;

	public function __construct( RequestPreconditionCheck $preconditionCheck ) {
		$this->preconditionCheck = $preconditionCheck;
	}

	public function run( Handler $handler, callable $runNext ): Response {
		$preconditionCheckResult = $this->preconditionCheck->checkPreconditions( $handler->getRequest() );
		if ( $preconditionCheckResult->getStatusCode() === 412 ) {
			$response = $handler->getResponseFactory()->createNoContent();
			$response->setStatus( 412 );

			return $response;
		}

		return $runNext();
	}

}
