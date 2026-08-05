<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Module\Module;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Router;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\GetOpenApiDocRouteHandler;
use Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner;

/**
 * @covers \Wikibase\Repo\RestApi\GetOpenApiDocRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetOpenApiDocRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private const OPENAPI_FILE = __DIR__ . '/../../src/openapi.json';
	private const EXAMPLE_PATH = '/v0/examples/{example_id}';
	private const EXAMPLE_PARTS_PATH = '/v0/examples/{example_id}/parts';

	protected function setUp(): void {
		parent::setUp();

		// installed extensions join their own fragments through this hook;
		// clear their handlers so only each test's fixture is in play
		$this->clearHook( 'WikibaseRepoOpenApiDocFragments' );
	}

	public function testGivenNoJoinedFragments_servesCommittedDocumentVerbatim(): void {
		$response = $this->newHandler( $this->newRouterWithRoutes( [] ) )->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'application/json', $response->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( file_get_contents( self::OPENAPI_FILE ), (string)$response->getBody() );
	}

	public function testGivenJoinedFragmentWithRoutablePaths_servesJoinedDocument(): void {
		$this->joinFragment( __DIR__ . '/data/valid-fragment.json' );

		$handler = $this->newHandler( $this->newRouterWithRoutes( [
			'' => [ '/wikibase' . self::EXAMPLE_PATH, '/wikibase' . self::EXAMPLE_PARTS_PATH ],
		] ) );
		$served = json_decode( (string)$handler->execute()->getBody(), true );
		$baseDoc = json_decode( file_get_contents( self::OPENAPI_FILE ), true );
		$fragment = json_decode( file_get_contents( __DIR__ . '/data/valid-fragment.json' ), true );

		$this->assertSame( $fragment['paths'][self::EXAMPLE_PATH], $served['paths'][self::EXAMPLE_PATH] );
		$this->assertSame(
			$fragment['paths'][self::EXAMPLE_PARTS_PATH],
			$served['paths'][self::EXAMPLE_PARTS_PATH]
		);
		foreach ( $baseDoc['paths'] as $path => $pathSpec ) {
			$this->assertSame( $pathSpec, $served['paths'][$path] );
		}
		$this->assertContains( $fragment['tags'][0], $served['tags'] );
		$this->assertSame( $baseDoc['components'], $served['components'] );
		$this->assertSame( $baseDoc['info'], $served['info'] );
	}

	public function testGivenFragmentWithPartiallyRoutablePaths_servesOnlyRoutableOnes(): void {
		$this->joinFragment( __DIR__ . '/data/valid-fragment.json' );

		$handler = $this->newHandler( $this->newRouterWithRoutes( [
			'' => [ '/wikibase' . self::EXAMPLE_PATH ],
		] ) );
		$served = json_decode( (string)$handler->execute()->getBody(), true );

		$this->assertArrayHasKey( self::EXAMPLE_PATH, $served['paths'] );
		$this->assertArrayNotHasKey( self::EXAMPLE_PARTS_PATH, $served['paths'] );
	}

	public function testGivenFragmentWithoutRoutablePaths_servesCommittedDocumentVerbatim(): void {
		$this->joinFragment( __DIR__ . '/data/valid-fragment.json' );

		$handler = $this->newHandler( $this->newRouterWithRoutes( [
			'wikibase/v1' => [ '/wikibase/v1/openapi.json' ],
		] ) );
		$response = $handler->execute();

		$this->assertSame( file_get_contents( self::OPENAPI_FILE ), (string)$response->getBody() );
	}

	public function testGivenFragmentRedefinesExistingPath_throws(): void {
		$this->joinFragment( __DIR__ . '/data/duplicate-path-fragment.json' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( "redefines path '/v1/openapi.json'" );

		$this->newHandler( $this->newRouterWithRoutes( [
			'wikibase/v1' => [ '/wikibase/v1/openapi.json' ],
		] ) )->execute();
	}

	private function joinFragment( string $file ): void {
		$this->setTemporaryHook(
			'WikibaseRepoOpenApiDocFragments',
			static function ( OpenApiDocFragmentJoiner $joiner ) use ( $file ): void {
				$joiner->join( json_decode( file_get_contents( $file ), true ), $file );
			}
		);
	}

	/**
	 * @param array<string,string[]> $routesPerModule full route paths, keyed by module id
	 */
	private function newRouterWithRoutes( array $routesPerModule ): Router {
		$modules = [];
		foreach ( $routesPerModule as $moduleId => $fullPaths ) {
			$modulePrefixLength = $moduleId === '' ? 0 : strlen( "/$moduleId" );
			$definedPaths = [];
			foreach ( $fullPaths as $fullPath ) {
				$definedPaths[ substr( $fullPath, $modulePrefixLength ) ] = [ 'GET' ];
			}

			$module = $this->createStub( Module::class );
			$module->method( 'getDefinedPaths' )->willReturn( $definedPaths );
			$modules[$moduleId] = $module;
		}

		$router = $this->createNoOpMock( Router::class, [ 'getModuleIds', 'getModule' ] );
		$router->method( 'getModuleIds' )->willReturn( array_keys( $modules ) );
		$router->method( 'getModule' )->willReturnCallback(
			fn( string $name ) => $modules[$name] ?? null
		);

		return $router;
	}

	public function testCommittedDocumentContainsNoEmptyObjects(): void {
		// joining decodes the document to associative arrays and re-encodes it,
		// which turns an empty JSON object into an empty JSON array; until the
		// joiner works on stdClass, the committed document must not rely on
		// empty objects surviving the roundtrip
		$this->assertDoesNotMatchRegularExpression(
			'/\{\s*\}/',
			file_get_contents( self::OPENAPI_FILE )
		);
	}

	private function newHandler( ?Router $router = null ): Handler {
		$handler = GetOpenApiDocRouteHandler::factory();
		$this->initHandler(
			$handler,
			new RequestData( [ 'method' => 'GET' ] ),
			[],
			[],
			null,
			null,
			$router
		);
		$this->validateHandler( $handler );

		return $handler;
	}

}
