<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\Middleware;

/**
 * @license GPL-2.0-or-later
 */
class ResponseHeaderCountingTestMiddleware implements Middleware {
	public const MIDDLEWARE_COUNT_HEADER = 'X-MIDDLEWARES-CALL-COUNT';

	private TestCase $test;
	private int $expectedCount;

	public function __construct( TestCase $test, int $expectedCount ) {
		$this->test = $test;
		$this->expectedCount = $expectedCount;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		/** @var Response $response */
		$response = $runNext();
		$callCount = intval( $response->getHeaderLine( self::MIDDLEWARE_COUNT_HEADER ) ) + 1;
		$this->test->assertSame( $this->expectedCount, $callCount );
		$response->setHeader( self::MIDDLEWARE_COUNT_HEADER, $callCount );

		return $response;
	}

}
