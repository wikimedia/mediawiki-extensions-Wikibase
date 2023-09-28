<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyAliasesInLanguageRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyAliasesInLanguageRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguageRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;
	use RestHandlerTestUtilsTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setMockPreconditionMiddlewareFactory();
	}

	public function testHandlesErrors(): void {
		$useCase = $this->createStub( GetPropertyAliasesInLanguage::class );
		$useCase->method( 'execute' )->willThrowException(
			new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, 'Could not find a property with the ID: P321' )
		);

		$this->setService( 'WbRestApi.GetPropertyAliasesInLanguage', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( UseCaseError::PROPERTY_NOT_FOUND, $responseBody->code );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetPropertyAliasesInLanguageRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123', 'language_code' => 'en' ],
			] ),
			[ 'path' => '/entities/properties/{property_id}/aliases/{language_code}' ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
