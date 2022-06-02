<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use Psr\Log\LoggerInterface;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandler {

	private $responseFactory;
	private $logger;

	public function __construct( ResponseFactory $responseFactory, LoggerInterface $logger ) {
		$this->responseFactory = $responseFactory;
		$this->logger = $logger;
	}

	/**
	 * @return mixed|Response
	 */
	public function runWithErrorHandling( callable $run, array $args ) {
		try {
			return $run( ...$args );
		} catch ( \Exception $exception ) {
			$this->logger->debug( (string)$exception );

			return $this->responseFactory->newErrorResponse(
				new ErrorResponse( ErrorResponse::UNEXPECTED_ERROR, 'Unexpected error' )
			);
		}
	}

}
