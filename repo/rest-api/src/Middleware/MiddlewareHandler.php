<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class MiddlewareHandler {

	/**
	 * @var Middleware[]
	 */
	private array $middlewares;

	public function __construct( array $middlewares ) {
		Assert::parameter(
			count( $middlewares ) > 0,
			'$middlewares',
			'must not be empty'
		);
		$this->middlewares = $middlewares;
	}

	public function run( Handler $routeHandler, callable $runRoute ): Response {
		return $this->callMiddlewaresRecursively(
			$this->middlewares,
			$routeHandler,
			$runRoute
		);
	}

	private function callMiddlewaresRecursively(
		array $remainingMiddlewares,
		Handler $routeHandler,
		callable $runRouteWithArgs
	): Response {
		$currentMiddleware = array_shift( $remainingMiddlewares );

		// Each middleware runs the next one. The last one runs $runRouteWithArgs.
		$runNext = !$remainingMiddlewares ?
			$runRouteWithArgs :
			fn() => $this->callMiddlewaresRecursively( $remainingMiddlewares, $routeHandler, $runRouteWithArgs );

		return $currentMiddleware->run( $routeHandler, $runNext );
	}

}
