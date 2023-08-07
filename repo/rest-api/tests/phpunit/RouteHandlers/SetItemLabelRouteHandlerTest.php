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
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\RouteHandlers\SetItemLabelRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\SetItemLabelRouteHandler
 * @group Wikibase
 * @group Database
 * @license GPL-2.0-or-later
 */
class SetItemLabelRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	/**
	 * @dataProvider provideWasReplacedAndStatusCode
	 */
	public function testValidSuccessHttpResponse( bool $wasReplaced, int $statusCode ): void {
		$label = 'test label';
		$useCaseResponse = new SetItemLabelResponse( new Label( 'en', $label ), '20230731042031', 42, $wasReplaced );
		$useCase = $this->createStub( SetItemLabel::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.SetItemLabel', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( $statusCode, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Mon, 31 Jul 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$this->assertJsonStringEqualsJsonString( json_encode( $label ), $response->getBody()->getContents() );
	}

	public function provideWasReplacedAndStatusCode(): Generator {
		yield 'Description was replaced' => [ true, 200 ];
		yield 'Description was added' => [ false, 201 ];
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createMock( SetItemLabel::class );
		$useCase->expects( $this->once() )->method( 'execute' )->willThrowException( $exception );

		$this->setService( 'WbRestApi.SetItemLabel', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( $expectedErrorCode, $responseBody->code );
	}

	public function provideExceptionAndExpectedErrorCode(): Generator {
		yield 'Error handled by ResponseFactory' => [
			new UseCaseError( UseCaseError::INVALID_LABEL, '' ),
			UseCaseError::INVALID_LABEL,
		];

		yield 'Item Redirect' => [ new ItemRedirect( 'Q123' ), UseCaseError::ITEM_REDIRECTED ];

		yield 'Unexpected Error' => [ new RuntimeException(), UseCaseError::UNEXPECTED_ERROR ];
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertTrue( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = SetItemLabelRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'PUT',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => [ 'item_id' => 'Q123', 'language_code' => 'en' ],
				'bodyContents' => json_encode( [ 'label' => 'label text' ] ),
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
