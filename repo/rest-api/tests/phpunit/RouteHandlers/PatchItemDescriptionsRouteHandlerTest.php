<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Throwable;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\RouteHandlers\PatchItemDescriptionsRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\PatchItemDescriptionsRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidSuccessHttpResponse(): void {
		$enDescription = 'test description';
		$arDescription = 'وصف الاختبار';
		$descriptions = new Descriptions( new Description( 'en', $enDescription ), new Description( 'ar', $arDescription ) );
		$useCase = $this->createStub( PatchItemDescriptions::class );
		$useCase->method( 'execute' )->willReturn( new PatchItemDescriptionsResponse( $descriptions ) );

		$this->setService( 'WbRestApi.PatchItemDescriptions', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$expectedDescriptions = [ 'en' => $enDescription, 'ar' => $arDescription ];
		$this->assertJsonStringEqualsJsonString( json_encode( $expectedDescriptions ), $response->getBody()->getContents() );
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createStub( PatchItemDescriptions::class );
		$useCase->method( 'execute' )->willThrowException( $exception );

		$this->setService( 'WbRestApi.PatchItemDescriptions', $useCase );
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

		yield 'Item Redirect' => [ new ItemRedirect( 'Q123' ), UseCaseError::ITEM_REDIRECTED ];
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = PatchItemDescriptionsRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'PATCH',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => [
					PatchItemDescriptionsRouteHandler::ITEM_ID_PATH_PARAM => 'Q123',
				],
				'bodyContents' => json_encode( [
					'patch' => [ [ 'op' => 'remove', 'path' => '/en' ] ],
				] ),
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
