<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Response;
use Throwable;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerMiddleware implements Middleware {

	private ResponseFactory $responseFactory;
	private ErrorReporter $errorReporter;

	public function __construct(
		ResponseFactory $responseFactory,
		ErrorReporter $errorReporter
	) {
		$this->responseFactory = $responseFactory;
		$this->errorReporter = $errorReporter;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		try {
			return $runNext();
		} catch ( Throwable $exception ) {
			$this->errorReporter->reportError( $exception, $routeHandler, $routeHandler->getRequest() );

			return $this->responseFactory->newErrorResponse(
				new ErrorResponse( ErrorResponse::UNEXPECTED_ERROR, 'Unexpected error' )
			);
		}
	}

}
