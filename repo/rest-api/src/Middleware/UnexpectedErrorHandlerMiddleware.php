<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerMiddleware implements Middleware {

	public const ERROR_CODE = 'unexpected-error';
	private ErrorReporter $errorReporter;

	public function __construct(
		ErrorReporter $errorReporter
	) {
		$this->errorReporter = $errorReporter;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		try {
			return $runNext();
		} catch ( HttpException $exception ) {
			throw $exception; // other middlewares may throw those so we just rethrow
		} catch ( Throwable $exception ) {
			$this->errorReporter->reportError( $exception, $routeHandler, $routeHandler->getRequest() );

			$httpResponse = new Response();
			$httpResponse->setHeader( 'Content-Type', 'application/json' );
			$httpResponse->setHeader( 'Content-Language', 'en' );
			$httpResponse->setStatus( 500 );
			$httpResponse->setBody( new StringStream( json_encode(
				[ 'code' => self::ERROR_CODE, 'message' => 'Unexpected error' ],
				JSON_UNESCAPED_SLASHES
			) ) );

			return $httpResponse;
		}
	}

}
