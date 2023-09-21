<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyDescriptionsRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyDescriptionsRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionsRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidSuccessHttpResponse(): void {
		$enDescription = 'test description';
		$deDescription = 'Test-Beschreibung';
		$descriptions = new Descriptions(
			new Description( 'en', $enDescription ),
			new Description( 'de', $deDescription )
		);
		$useCaseResponse = new GetPropertyDescriptionsResponse( $descriptions, '20230831042031', 42 );
		$useCase = $this->createStub( GetPropertyDescriptions::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyDescriptions', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Thu, 31 Aug 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$this->assertJsonStringEqualsJsonString(
			json_encode( [ 'en' => $enDescription, 'de' => $deDescription ] ),
			$response->getBody()->getContents()
		);
	}

	public function testHandlesErrors(): void {
		$useCase = $this->createStub( GetPropertyDescriptions::class );
		$useCase->method( 'execute' )->willThrowException(
			new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, 'Could not find a property with the ID: P321' )
		);

		$this->setService( 'WbRestApi.GetPropertyDescriptions', $useCase );
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
		$routeHandler = GetPropertyDescriptionsRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123' ],
			] ),
			[ 'path' => '/entities/properties/{property_id}/descriptions' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
