<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use ExtensionRegistry;
use Generator;
use LogicException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\RouteHandlers\RestfulSearchNotAvailableRouteHandler;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RouteHandlersTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private static array $searchRoutesData = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$searchRoutes = fn( $route ) => str_starts_with( $route['path'], '/wikibase/v0' );
		$prodRoutes = array_filter(
			json_decode( file_get_contents( __DIR__ . '/../../../../../../extension-repo.json' ), true )[ 'RestRoutes' ],
			$searchRoutes
		);
		self::$searchRoutesData = array_merge(
			$prodRoutes,
			array_filter(
				json_decode( file_get_contents( __DIR__ . '/../../../../../rest-api/routes.dev.json' ), true ),
				$searchRoutes
			)
		);
	}

	protected function setUp(): void {
		parent::setUp();

		// suppress error reporting to avoid CI failures caused by errors in the logs
		$this->setService( 'WbSearch.ErrorReporter', $this->createStub( ErrorReporter::class ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesSearchNotAvailable( array $routeHandlerData ): void {
		$this->simulateSearchEnabled( false );

		$this->setService( $routeHandlerData['serviceName'], $this->createStub( $routeHandlerData['useCase'] ) );

		$response = $this->newHandlerWithValidRequest( $routeHandlerData )->execute();

		self::assertSame( 'search-not-available', json_decode( $response->getBody()->getContents() )->code );
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesUnexpectedErrors( array $routeHandlerData ): void {
		$this->simulateSearchEnabled();
		$useCase = $this->createMock( $routeHandlerData[ 'useCase' ] );
		$useCase->expects( $this->once() )
			->method( 'execute' )
			->willThrowException( new RuntimeException() );
		$this->setService( $routeHandlerData[ 'serviceName' ], $useCase );

		$response = $this->newHandlerWithValidRequest( $routeHandlerData )->execute();

		self::assertSame( UnexpectedErrorHandlerMiddleware::ERROR_CODE, json_decode( $response->getBody()->getContents() )->code );
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testReadWriteAccess( array $routeHandlerData ): void {
		$this->simulateSearchEnabled();
		$this->setService( $routeHandlerData['serviceName'], $this->createStub( $routeHandlerData['useCase'] ) );

		$routeHandler = $this->newHandlerWithValidRequest( $routeHandlerData );

		self::assertTrue( $routeHandler->needsReadAccess() );
		self::assertFalse( $routeHandler->needsWriteAccess() );
	}

	public function testSearchUnavailableReadWriteAccess(): void {
		$routeHandler = new RestfulSearchNotAvailableRouteHandler();

		self::assertTrue( $routeHandler->needsReadAccess() );
		self::assertFalse( $routeHandler->needsWriteAccess() );
	}

	public static function routeHandlersProvider(): Generator {
		// phpcs:disable Generic.Arrays.ArrayIndent.CloseBraceNotNewLine
		yield 'SimpleItemSearch' => [ [
			'useCase' => SimpleItemSearch::class,
			'path' => '/wikibase/v0/search/items',
			'serviceName' => 'WbSearch.SimpleItemSearch',
		] ];

		yield 'SimplePropertySearch' => [ [
			'useCase' => SimplePropertySearch::class,
			'path' => '/wikibase/v0/search/properties',
			'serviceName' => 'WbSearch.SimplePropertySearch',
		] ];

		yield 'ItemPrefixSearch' => [ [
			'useCase' => ItemPrefixSearch::class,
			'path' => '/wikibase/v0/suggest/items',
			'serviceName' => 'WbSearch.ItemPrefixSearch',
		] ];

		yield 'PropertyPrefixSearch' => [ [
			'useCase' => PropertyPrefixSearch::class,
			'path' => '/wikibase/v0/suggest/properties',
			'serviceName' => 'WbSearch.PropertyPrefixSearch',
		] ];
	}

	private static function getRouteForUseCase( string $useCaseClass ): array {
		$classNameParts = explode( '\\', $useCaseClass );
		$useCaseName = end( $classNameParts );

		foreach ( self::$searchRoutesData as $route ) {
			if ( strpos( $route['factory'], "\\{$useCaseName}RouteHandler" ) ) {
				return $route;
			}
		}

		throw new LogicException( "No route found for use case $useCaseName" );
	}

	private function newHandlerWithValidRequest( array $routeHandlerData ): Handler {
		$routeHandler = $this->getRouteForUseCase( $routeHandlerData[ 'useCase' ] )['factory']();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'GET',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'queryParams' => [ 'language' => 'en', 'q' => 'search term' ],
				'bodyContents' => null,
			] ),
			[ 'path' => $routeHandlerData[ 'path' ] ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

	private function simulateSearchEnabled( bool $enabled = true ): void {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );

		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->will(
				$enabled
				? $this->returnCallback( fn( string $extensionName ) => $extensionName === 'WikibaseCirrusSearch' )
				: $this->returnValue( false )
			);

		$this->setMwGlobals( 'wgSearchType', $enabled ? 'CirrusSearch' : null );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );
	}
}
