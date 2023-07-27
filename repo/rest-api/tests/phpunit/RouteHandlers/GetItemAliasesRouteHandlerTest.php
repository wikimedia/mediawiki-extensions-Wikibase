<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Throwable;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\RouteHandlers\GetItemAliasesRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetItemAliasesRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidSuccessHttpResponse(): void {
		$enAliases = [ 'alias one', 'second alias' ];
		$arAliases = [ 'الاسم المستعار واحد', 'الاسم المستعار الثاني' ];
		$aliases = new Aliases( new AliasesInLanguage( 'en', $enAliases ), new AliasesInLanguage( 'ar', $arAliases ) );
		$useCaseResponse = new GetItemAliasesResponse( $aliases, '20230731042031', 42 );
		$useCase = $this->createStub( GetItemAliases::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetItemAliases', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Mon, 31 Jul 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$expectedAliases = [ 'en' => $enAliases, 'ar' => $arAliases ];
		$this->assertJsonStringEqualsJsonString( json_encode( $expectedAliases ), $response->getBody()->getContents() );
	}

	public function testValidRedirectHttpResponse(): void {
		$redirectTargetId = 'Q123';
		$useCase = $this->createStub( GetItemAliases::class );
		$useCase->method( 'execute' )->willThrowException( new ItemRedirect( $redirectTargetId ) );

		$this->setService( 'WbRestApi.GetItemAliases', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 308, $response->getStatusCode() );
		$this->assertStringContainsString( $redirectTargetId, $response->getHeaderLine( 'Location' ) );
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createStub( GetItemAliases::class );
		$useCase->method( 'execute' )->willThrowException( $exception );

		$this->setService( 'WbRestApi.GetItemAliases', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( $expectedErrorCode, $responseBody->code );
	}

	public function provideExceptionAndExpectedErrorCode(): Generator {
		yield 'Error handled by ResponseFactory' => [
			new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
			UseCaseError::INVALID_ITEM_ID,
		];

		yield 'Unexpected Error' => [ new RuntimeException(), UseCaseError::UNEXPECTED_ERROR ];
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetItemAliasesRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'item_id' => 'Q123' ],
			] ),
			[ 'path' => '/entities/items/{item_id}/aliases' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}
}
