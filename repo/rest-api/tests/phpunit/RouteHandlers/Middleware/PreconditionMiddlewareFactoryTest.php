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

	public function testNewPreconditionMiddleware(): void {
		$itemId = new ItemId( 'Q42' );

		$metadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		$middleware = ( new PreconditionMiddlewareFactory( $metadataRetriever, new ConditionalHeaderUtil() ) )
			->newPreconditionMiddleware( function () use ( $itemId ) {
				return $itemId->getSerialization();
			} );

		$middleware->run( $this->newRouteHandler(), function () {
			return $this->createStub( Response::class );
		} );
	}

	private function newRouteHandler(): Handler {
		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getRequest' )->willReturn( new RequestData() );

		return $routeHandler;
	}

}
