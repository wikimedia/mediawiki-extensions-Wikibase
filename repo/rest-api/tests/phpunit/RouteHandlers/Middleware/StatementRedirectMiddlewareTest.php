<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use MediaWiki\Rest\SimpleHandler;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\StatementRedirectMiddleware;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\StatementRedirectMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementRedirectMiddlewareTest extends TestCase {

	private const SUBJECT_ID_PATH_PARAM = 'subject_id';
	private const STATEMENT_ID_PATH_PARAM = 'statement_id';

	public function testGivenRequestedStatementFound_returnsResponse(): void {
		$middleware = new StatementRedirectMiddleware(
			$this->createMock( EntityIdParser::class ),
			$this->createMock( StatementSubjectRetriever::class ),
			self::STATEMENT_ID_PATH_PARAM,
			self::SUBJECT_ID_PATH_PARAM
		);

		$expectedResponse = new Response();
		$response = $middleware->run(
			$this->createStub( SimpleHandler::class ),
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenSubjectFromIdNotFound_returnsResponse(): void {
		$requestedSubjectId = 'Q99999';
		$requestedStatementId = 'Q99999$9C1C4B59-D336-408D-9FE8-1035F45A2CF1';
		// this stub will not find a statement subject
		$statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );

		$middleware = new StatementRedirectMiddleware(
			WikibaseRepo::getEntityIdParser(),
			$statementSubjectRetriever,
			self::STATEMENT_ID_PATH_PARAM,
			self::SUBJECT_ID_PATH_PARAM
		);

		$expectedResponse = new Response( '{ "code": "statement-not-found" }' );
		$expectedResponse->setStatus( 404 );
		$response = $middleware->run(
			$this->newRouteHandlerWithRequest( $requestedStatementId, $requestedSubjectId ),
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenSubjectFromStatementIdNotFound_returnsResponse(): void {
		$requestedStatementId = 'Q99999$9C1C4B59-D336-408D-9FE8-1035F45A2CF1';
		// this stub will not find a statement subject
		$statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );

		$middleware = new StatementRedirectMiddleware(
			WikibaseRepo::getEntityIdParser(),
			$statementSubjectRetriever,
			self::STATEMENT_ID_PATH_PARAM,
			null
		);

		$expectedResponse = new Response( '{ "code": "statement-not-found" }' );
		$expectedResponse->setStatus( 404 );
		$response = $middleware->run(
			$this->newRouteHandlerWithRequest( $requestedStatementId, null ),
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenLowercaseStatementIdWithSubjectId_redirects(): void {
		$requestedStatementId = 'q1$9C1C4B59-D336-408D-9FE8-1035F45A2CF1';
		$actualStatementId = strtoupper( $requestedStatementId );

		$middleware = new StatementRedirectMiddleware(
			WikibaseRepo::getEntityIdParser(),
			$this->newStatementSubjectRetrieverWithSubject( $actualStatementId ),
			self::STATEMENT_ID_PATH_PARAM,
			self::SUBJECT_ID_PATH_PARAM
		);

		$routeHandler = $this->newRouteHandlerWithRequest( $requestedStatementId, 'Q1' );
		$response = new Response( '{ "code": "statement-not-found" }' );
		$response->setStatus( 404 );
		$response = $middleware->run( $routeHandler, fn() => $response );

		$this->assertSame( 308, $response->getStatusCode() );
		$this->assertEquals(
			str_replace( $requestedStatementId, $actualStatementId, $routeHandler->getRequest()->getUri() ),
			$response->getHeaderLine( 'Location' )
		);
	}

	public function testGivenLowercaseStatementIdWithoutSubjectId_redirects(): void {
		$requestedStatementId = 'q1$9C1C4B59-D336-408D-9FE8-1035F45A2CF1';
		$actualStatementId = strtoupper( $requestedStatementId );

		$middleware = new StatementRedirectMiddleware(
			WikibaseRepo::getEntityIdParser(),
			$this->newStatementSubjectRetrieverWithSubject( $actualStatementId ),
			self::STATEMENT_ID_PATH_PARAM,
			null
		);

		$response = new Response( '{ "code": "statement-not-found" }' );
		$response->setStatus( 404 );

		$routeHandler = $this->newRouteHandlerWithRequest( $requestedStatementId, null );
		$response = $middleware->run(
			$routeHandler,
			fn() => $response
		);

		$this->assertSame( 308, $response->getStatusCode() );
		$this->assertEquals(
			str_replace( $requestedStatementId, $actualStatementId, $routeHandler->getRequest()->getUri() ),
			$response->getHeaderLine( 'Location' )
		);
	}

	private function newStatementSubjectRetrieverWithSubject( string $actualStatementId ): StatementSubjectRetriever {
		$statement = $this->createStub( Statement::class );
		$statement->method( 'getGuid' )->willReturn( $actualStatementId );

		$statementSubject = $this->createStub( StatementListProvidingEntity::class );
		$statementSubject->method( 'getStatements' )
			->willReturn( new StatementList( $statement ) );

		$statementSubjectRetriever = $this->createMock( StatementSubjectRetriever::class );
		$statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( $statementSubject );

		return $statementSubjectRetriever;
	}

	private function newRouteHandlerWithRequest( string $requestedStatementId, ?string $requestedSubjectId ): SimpleHandler {
		$request = $this->createMock( RequestInterface::class );

		$request->method( 'getPathParam' )->willReturnMap(
			[
				[ self::STATEMENT_ID_PATH_PARAM, $requestedStatementId ],
				[ self::SUBJECT_ID_PATH_PARAM, $requestedSubjectId ],
			]
		);
		$request->method( 'getUri' )
			->willReturn( "request-url-with-$requestedStatementId" );

		$routeHandler = $this->createMock( SimpleHandler::class );
		$routeHandler->method( 'getRequest' )->willReturn( $request );
		$routeHandler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );

		return $routeHandler;
	}
}
