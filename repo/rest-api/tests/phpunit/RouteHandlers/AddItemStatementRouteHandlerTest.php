<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\RouteHandlers\AddItemStatementRouteHandler;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\AddItemStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private $itemId;
	private $statement;
	private $tags;
	private $bot;

	protected function setUp(): void {
		parent::setUp();
		$this->itemId = 'Q123';
		$this->statement = [
			'mainsnak' => [
				'snaktype' => "value",
				'property' => "P1",
				'datavalue' => [
					'type' => "string",
					'value' => "I am a goat"
				],
				'hash' => "455481eeac76e6a8af71a6b493c073d54788e7e9"
			],
			'rank' => "preferred",
			'references' => []

		];
		$this->tags = [ 'edit', 'tags' ];
		$this->bot = true;
	}

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( AddItemStatement::class );
		$useCase->method( 'execute' )->willThrowException( new \RuntimeException() );
		$this->setService( 'WbRestApi.AddItemStatement', $useCase );

		$routeHandler = $this->newRequest();
		$this->validateHandler( $routeHandler );

		$response = $routeHandler->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( ErrorResponse::UNEXPECTED_ERROR, $responseBody->code );
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertTrue( $routeHandler->needsWriteAccess() );
	}

	private function newRequest(): Handler {
		$routeHandler = AddItemStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
					'method' => 'POST',
					'headers' => [ 'Content-Type' => 'application/json' ],
					'pathParams' => [
						AddItemStatementRouteHandler::ITEM_ID_PATH_PARAM => $this->itemId
					],
					'bodyContents' => json_encode( [
						'statement' => $this->statement,
						'tags' => $this->tags,
						'bot' => $this->bot,
					] )
				]
			)
		);
		return $routeHandler;
	}
}
