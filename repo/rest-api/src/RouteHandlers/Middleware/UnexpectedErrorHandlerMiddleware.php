<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Psr\Log\LoggerInterface;
use Throwable;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerMiddleware implements Middleware {

	private ResponseFactory $responseFactory;
	private LoggerInterface $logger;

	public function __construct( ResponseFactory $responseFactory, LoggerInterface $logger ) {
		$this->responseFactory = $responseFactory;
		$this->logger = $logger;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		try {
			return $runNext();
		} catch ( Throwable $exception ) {
			$this->logger->error( (string)$exception );

			return $this->responseFactory->newErrorResponse(
				new ErrorResponse( ErrorResponse::UNEXPECTED_ERROR, 'Unexpected error' )
			);
		}
	}

}
