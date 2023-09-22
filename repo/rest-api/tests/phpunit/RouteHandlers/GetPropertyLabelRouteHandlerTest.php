<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyLabelRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyLabelRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;
	use RestHandlerTestUtilsTrait;

	public function testValidSuccessHttpResponse(): void {
		$label = 'test label';
		$useCaseResponse = new GetPropertyLabelResponse( new Label( 'en', $label ) );
		$useCase = $this->createStub( GetPropertyLabel::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyLabel', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertJsonStringEqualsJsonString( json_encode( $label ), $response->getBody()->getContents() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetPropertyLabelRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123', 'language_code' => 'en' ],
			] ),
			[ 'path' => '/entities/properties/{property_id}/labels/{language_code}' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
