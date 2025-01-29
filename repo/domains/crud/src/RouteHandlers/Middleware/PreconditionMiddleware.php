<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class PreconditionMiddleware implements Middleware {

	private RequestPreconditionCheck $preconditionCheck;

	public function __construct( RequestPreconditionCheck $preconditionCheck ) {
		$this->preconditionCheck = $preconditionCheck;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$preconditionCheckResult = $this->preconditionCheck->checkPreconditions( $routeHandler->getRequest() );
		switch ( $preconditionCheckResult->getStatusCode() ) {
			case 304:
				$notModifiedResponse = $routeHandler->getResponseFactory()->createNotModified();
				$notModifiedResponse->setHeader( 'ETag', '"' . $preconditionCheckResult->getRevisionId() . '"' );

				return $notModifiedResponse;
			case 412:
				$response = $routeHandler->getResponseFactory()->createNoContent();
				$response->setStatus( 412 );

				return $response;
			default:
				return $runNext();
		}
	}
}
