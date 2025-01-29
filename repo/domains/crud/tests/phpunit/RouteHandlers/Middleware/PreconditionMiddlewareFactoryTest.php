<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\WikibaseRepo;

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

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $itemId )
			->willReturn( LatestRevisionIdResult::nonexistentEntity() );

		$middleware = ( new PreconditionMiddlewareFactory(
			$entityRevisionLookup,
			WikibaseRepo::getEntityIdParser(),
			new ConditionalHeaderUtil()
		) )->newPreconditionMiddleware( fn() => $itemId->getSerialization() );

		$middleware->run( $this->newRouteHandler(), fn() => $this->createStub( Response::class ) );
	}

	private function newRouteHandler(): Handler {
		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getRequest' )->willReturn( new RequestData() );

		return $routeHandler;
	}

}
