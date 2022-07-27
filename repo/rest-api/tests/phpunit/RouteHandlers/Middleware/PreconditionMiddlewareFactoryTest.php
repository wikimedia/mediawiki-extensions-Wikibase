<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareFactoryTest extends TestCase {

	public function testNewNotModifiedPreconditionMiddleware(): void {
		$itemId = new ItemId( 'Q42' );
		$middleware = ( new PreconditionMiddlewareFactory(
			$this->newMetadataRetrieverExpectingItemId( $itemId ),
			new ConditionalHeaderUtil()
		) )->newNotModifiedPreconditionMiddleware( function () use ( $itemId ) {
			return $itemId->getSerialization();
		} );

		$middleware->run( $this->newHandler(), function () {
			return $this->createStub( Response::class );
		} );
	}

	public function testNewModifiedPreconditionMiddleware(): void {
		$itemId = new ItemId( 'Q42' );
		$middleware = ( new PreconditionMiddlewareFactory(
			$this->newMetadataRetrieverExpectingItemId( $itemId ),
			new ConditionalHeaderUtil()
		) )->newModifiedPreconditionMiddleware( function () use ( $itemId ) {
			return $itemId->getSerialization();
		} );

		$middleware->run( $this->newHandler(), function () {
			return $this->createStub( Response::class );
		} );
	}

	private function newHandler(): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getRequest' )->willReturn( new RequestData() );

		return $handler;
	}

	private function newMetadataRetrieverExpectingItemId( ItemId $itemId ): ItemRevisionMetadataRetriever {
		$metadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		return $metadataRetriever;
	}

}
