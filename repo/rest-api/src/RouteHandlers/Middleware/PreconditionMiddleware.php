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
				return $this->newNotModifiedResponse(
					$routeHandler,
					$preconditionCheckResult->getRevisionMetadata()->getRevisionId()
				);
			case 412:
				return $this->newPreconditionFailedResponse( $routeHandler );
			default:
				return $runNext();
		}
	}

	private function newNotModifiedResponse( Handler $routeHandler, int $revId ): Response {
		$notModifiedResponse = $routeHandler->getResponseFactory()->createNotModified();
		$notModifiedResponse->setHeader( 'ETag', "\"$revId\"" );

		return $notModifiedResponse;
	}

	private function newPreconditionFailedResponse( Handler $routeHandler ): Response {
		$response = $routeHandler->getResponseFactory()->createNoContent();
		$response->setStatus( 412 );

		return $response;
	}

}
