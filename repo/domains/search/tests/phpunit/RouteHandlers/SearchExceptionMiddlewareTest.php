<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Status\Status;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Domains\Search\RouteHandlers\SearchExceptionMiddleware;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\SearchExceptionMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SearchExceptionMiddlewareTest extends TestCase {

	public function testGivenEntitySearchException_throwsHttpException(): void {
		$this->expectException( HttpException::class );

		$this->newMiddleware()->run(
			$this->createStub( Handler::class ),
			fn() => throw new EntitySearchException( Status::newFatal( 'some search failure' ) )
		);
	}

	public function testGivenNoError_returnsRouteResponse(): void {
		$expectedResponse = $this->createStub( Response::class );

		$response = $this->newMiddleware()->run(
			$this->createStub( Handler::class ),
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

	private function newMiddleware(): SearchExceptionMiddleware {
		return new SearchExceptionMiddleware();
	}

}
