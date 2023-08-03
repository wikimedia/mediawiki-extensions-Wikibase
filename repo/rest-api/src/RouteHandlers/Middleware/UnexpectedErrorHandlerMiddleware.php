<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Response;
use Psr\Log\LoggerInterface;
use Throwable;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EntityUpdatePrevented;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerMiddleware implements Middleware {

	private ResponseFactory $responseFactory;
	private ErrorReporter $errorReporter;
	private LoggerInterface $logger;

	public function __construct(
		ResponseFactory $responseFactory,
		ErrorReporter $errorReporter,
		LoggerInterface $logger
	) {
		$this->responseFactory = $responseFactory;
		$this->errorReporter = $errorReporter;
		$this->logger = $logger;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		try {
			return $runNext();
		} catch ( EntityUpdatePrevented $exception ) { // temporary fix for T329233
			$this->logger->warning( $exception->getMessage(), [ 'exception' => $exception ] );
		} catch ( Throwable $exception ) {
			$this->errorReporter->reportError( $exception, $routeHandler, $routeHandler->getRequest() );
		}

		return $this->responseFactory->newErrorResponse(
			UseCaseError::UNEXPECTED_ERROR,
			'Unexpected error'
		);
	}

}
