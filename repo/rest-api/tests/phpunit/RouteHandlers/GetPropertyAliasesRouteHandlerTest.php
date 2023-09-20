<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyAliasesRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyAliasesRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidSuccessHttpResponse(): void {
		$enAliases = [ 'first alias', 'second alias' ];
		$arAliases = [ 'الاسم المستعار الثاني', 'الاسم المستعار الأول' ];
		$aliases = new Aliases( new AliasesInLanguage( 'en', $enAliases ), new AliasesInLanguage( 'ar', $arAliases ) );
		$useCaseResponse = new GetPropertyAliasesResponse( $aliases, '20230731042031', 42 );
		$useCase = $this->createStub( GetPropertyAliases::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyAliases', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Mon, 31 Jul 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$expectedAliases = [ 'en' => $enAliases, 'ar' => $arAliases ];
		$this->assertJsonStringEqualsJsonString( json_encode( $expectedAliases ), $response->getBody()->getContents() );
	}

	public function testHandlesErrors(): void {
		$useCase = $this->createStub( GetPropertyAliases::class );
		$useCase->method( 'execute' )->willThrowException(
			new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, 'Could not find a property with the ID: P321' )
		);

		$this->setService( 'WbRestApi.GetPropertyAliases', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( UseCaseError::PROPERTY_NOT_FOUND, $responseBody->code );
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetPropertyAliasesRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123' ],
			] ),
			[ 'path' => '/entities/properties/{property_id}/aliases' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}
}
