<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\NotModifiedPreconditionMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\NotModifiedPreconditionMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NotModifiedPreconditionMiddlewareTest extends TestCase {

	/**
	 * @dataProvider notModifiedResponseDataProvider
	 */
	public function testGivenHeadersMatchRevision_respondsNotModified(
		array $headers,
		LatestItemRevisionMetadataResult $revisionMetadataResult
	): void {
		$itemId = new ItemId( 'Q42' );
		$request = new RequestData( [
			'headers' => $headers,
		] );

		$middleware = new NotModifiedPreconditionMiddleware(
			$this->newMetadataRetrieverReturningResult( $itemId, $revisionMetadataResult ),
			function () use ( $itemId ) {
				return $itemId->getSerialization();
			}
		);

		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			function (): Response {
				$this->fail( 'This function should never be called in this scenario.' );
			}
		);

		$this->assertSame( 304, $response->getStatusCode() );
	}

	public function notModifiedResponseDataProvider(): Generator {
		yield 'revision id matches header' => [
			[ 'If-None-Match' => '"42"' ],
			LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
		];
		yield 'not modified since' => [
			[ 'If-Modified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111060606' ),
		];
	}

	public function testGivenInvalidItemId_doesNothing(): void {
		$middleware = new NotModifiedPreconditionMiddleware(
			$this->createStub( ItemRevisionMetadataRetriever::class ),
			function () {
				return 'some-invalid-item-id';
			}
		);
		$expectedResponse = $this->createStub( Response::class );
		$response = $middleware->run( $this->createStub( Handler::class ), function () use ( $expectedResponse ) {
			return $expectedResponse;
		} );

		$this->assertSame( $expectedResponse, $response );
	}

	/**
	 * @dataProvider mismatchingRevisionProvider
	 */
	public function testGivenHeadersDontMatchRevision_doesNothing(
		array $headers,
		LatestItemRevisionMetadataResult $revisionMetadataResult
	): void {
		$itemId = new ItemId( 'Q42' );
		$request = new RequestData( [
			'headers' => $headers,
		] );

		$middleware = new NotModifiedPreconditionMiddleware(
			$this->newMetadataRetrieverReturningResult( $itemId, $revisionMetadataResult ),
			function () use ( $itemId ) {
				return $itemId->getSerialization();
			}
		);

		$expectedResponse = $this->createStub( Response::class );
		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			function () use ( $expectedResponse ) {
				return $expectedResponse;
			}
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function mismatchingRevisionProvider(): Generator {
		yield 'outdated revision' => [
			[ 'If-None-Match' => '"41"' ],
			LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
		];
		yield 'item is a redirect' => [
			[ 'If-None-Match' => '"42"' ],
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q23' ) ),
		];
		yield 'item does not exist' => [
			[ 'If-None-Match' => '"42"' ],
			LatestItemRevisionMetadataResult::itemNotFound(),
		];
	}

	private function newHandlerWithRequest( RequestInterface $req ): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getRequest' )->willReturn( $req );
		$handler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );

		return $handler;
	}

	private function newMetadataRetrieverReturningResult(
		ItemId $id,
		LatestItemRevisionMetadataResult $result
	): ItemRevisionMetadataRetriever {
		$revisionMetadataLookup = $this->createMock( ItemRevisionMetadataRetriever::class );
		$revisionMetadataLookup->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $id )
			->willReturn( $result );

		return $revisionMetadataLookup;
	}

}
