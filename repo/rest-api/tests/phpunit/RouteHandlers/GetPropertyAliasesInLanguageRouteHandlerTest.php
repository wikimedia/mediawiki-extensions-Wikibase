<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
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

	public function testValidSuccessHttpResponse(): void {
		$aliases = [ 'is a', 'example of' ];
		$useCaseResponse = new GetPropertyAliasesInLanguageResponse( new AliasesInLanguage( 'en', $aliases ) );
		$useCase = $this->createStub( GetPropertyAliasesInLanguage::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyAliasesInLanguage', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertJsonStringEqualsJsonString(
			json_encode( $aliases ),
			$response->getBody()->getContents()
		);
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
