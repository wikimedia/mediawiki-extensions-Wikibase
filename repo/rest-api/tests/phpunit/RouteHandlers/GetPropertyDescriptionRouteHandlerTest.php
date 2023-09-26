<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyDescriptionRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyDescriptionRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidSuccessHttpResponse(): void {
		$descriptionText = 'test description';
		$useCaseResponse = new GetPropertyDescriptionResponse( new Description( 'en', $descriptionText ) );
		$useCase = $this->createStub( GetPropertyDescription::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyDescription', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertJsonStringEqualsJsonString(
			json_encode( $descriptionText ),
			$response->getBody()->getContents()
		);
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetPropertyDescriptionRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123', 'language_code' => 'en' ],
			] ),
			[ 'path' => '/entities/properties/{property_id}/descriptions/{language_code}' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
