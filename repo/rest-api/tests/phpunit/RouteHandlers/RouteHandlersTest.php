<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use LogicException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RouteHandlersTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;
	use RestHandlerTestUtilsTrait;

	private static array $routesData = [];
	private static array $prodRoutesData = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$prodRoutesData = json_decode( file_get_contents( __DIR__ . '/../../../routes.json' ), true );
		self::$routesData = array_merge(
			self::$prodRoutesData,
			json_decode( file_get_contents( __DIR__ . '/../../../routes.dev.json' ), true )
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->setMockPreconditionMiddlewareFactory();
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testReadWriteAccess( array $routeHandler ): void {
		$routeData = $this->getRouteForUseCase( $routeHandler['useCase'] );

		$routeHandler = $this->newHandlerWithValidRequest( $routeData, $routeHandler['validRequest'] );

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertSame( $routeData['method'] !== 'GET', $routeHandler->needsWriteAccess() );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesUnexpectedErrors( array $routeHandler ): void {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );
		$useCaseName = $this->getUseCaseName( $routeHandler['useCase'] );

		$this->setService( "WbRestApi.$useCaseName", $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( UseCaseError::UNEXPECTED_ERROR, json_decode( $response->getBody()->getContents() )->code );
	}

	public function routeHandlersProvider(): Generator {
		// phpcs:disable Generic.Arrays.ArrayIndent.CloseBraceNotNewLine
		yield 'AddItemStatement' => [ [
			'useCase' => AddItemStatement::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
		] ];
		yield 'AddPropertyStatement' => [ [
			'useCase' => AddPropertyStatement::class,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
		] ];
		yield 'GetItemAliasesInLanguage' => [ [
			'useCase' => GetItemAliasesInLanguage::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
		] ];
		yield 'GetItemAliases' => [ [
			'useCase' => GetItemAliases::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
		] ];
		yield 'GetItemDescription' => [ [
			'useCase' => GetItemDescription::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
		] ];
		yield 'GetItemDescriptions' => [ [
			'useCase' => GetItemDescriptions::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
		] ];
		yield 'GetItemLabel' => [ [
			'useCase' => GetItemLabel::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
		] ];
		yield 'GetItemLabels' => [ [
			'useCase' => GetItemLabels::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
		] ];
		yield 'GetItem' => [ [
			'useCase' => GetItem::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
		] ];
		yield 'GetItemStatement' => [ [
			'useCase' => GetItemStatement::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
		] ];
		yield 'GetItemStatements' => [ [
			'useCase' => GetItemStatements::class,
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
		] ];
		yield 'GetPropertyAliases' => [ [
			'useCase' => GetPropertyAliases::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
		] ];
		yield 'GetPropertyDescriptions' => [ [
			'useCase' => GetPropertyDescriptions::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
		] ];
		yield 'GetPropertyLabel' => [ [
			'useCase' => GetPropertyLabel::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
		] ];
		yield 'GetPropertyLabels' => [ [
			'useCase' => GetPropertyLabels::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
		] ];
		yield 'GetProperty' => [ [
			'useCase' => GetProperty::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
		] ];
		yield 'GetPropertyStatement' => [ [
			'useCase' => GetPropertyStatement::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
		] ];
		yield 'GetPropertyStatements' => [ [
			'useCase' => GetPropertyStatements::class,
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
		] ];
		yield 'GetStatement' => [ [
			'useCase' => GetStatement::class,
			'validRequest' => [ 'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
		] ];
		yield 'PatchItemLabels' => [ [
			'useCase' => PatchItemLabels::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/en' ] ] ],
			],
		] ];
		yield 'PatchItemStatement' => [ [
			'useCase' => PatchItemStatement::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
		] ];
		yield 'PatchPropertyStatement' => [ [
			'useCase' => PatchPropertyStatement::class,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
		] ];
		yield 'PatchStatement' => [ [
			'useCase' => PatchStatement::class,
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
		] ];
		yield 'RemoveItemStatement' => [ [
			'useCase' => RemoveItemStatement::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
		] ];
		yield 'RemovePropertyStatement' => [ [
			'useCase' => RemovePropertyStatement::class,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
		] ];
		yield 'RemoveStatement' => [ [
			'useCase' => RemoveStatement::class,
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
		] ];
		yield 'ReplaceItemStatement' => [ [
			'useCase' => ReplaceItemStatement::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
		] ];
		yield 'ReplacePropertyStatement' => [ [
			'useCase' => ReplacePropertyStatement::class,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
		] ];
		yield 'ReplaceStatement' => [ [
			'useCase' => ReplaceStatement::class,
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
		] ];
		yield 'SetItemLabel' => [ [
			'useCase' => SetItemLabel::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [ 'label' => 'potato' ],
			],
		] ];
		yield 'SetItemDescription' => [ [
			'useCase' => SetItemDescription::class,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [ 'description' => 'root vegetable' ],
			],
		] ];
		// phpcs:enable
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAllProductionRoutesAreCovered(): void {
		foreach ( self::$prodRoutesData as $route ) {
			foreach ( $this->routeHandlersProvider() as $routeTestData ) {
				if ( $route === $this->getRouteForUseCase( $routeTestData[0]['useCase'] ) ) {
					continue 2;
				}
			}
			$this->fail( "Route handler {$route['factory']} is not covered by any tests" );
		}
	}

	private function newHandlerWithValidRequest( array $routeData, array $validRequest ): Handler {
		$routeHandler = call_user_func( $routeData['factory'] );
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => $routeData['method'],
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => $validRequest['pathParams'],
				'bodyContents' => json_encode( $validRequest['bodyContents'] ?? null ),
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

	private function getRouteForUseCase( string $useCaseClass ): array {
		$useCaseName = $this->getUseCaseName( $useCaseClass );
		foreach ( self::$routesData as $route ) {
			if ( strpos( $route['factory'], "\\{$useCaseName}RouteHandler" ) ) {
				return $route;
			}
		}

		throw new LogicException( "No route found for use case $useCaseName" );
	}

	private function getUseCaseName( string $fqn ): string {
		$classNameParts = explode( '\\', $fqn );
		return $classNameParts[ count( $classNameParts ) - 1 ];
	}

	private function noValueStatementSerialization(): array {
		return [
			'property' => [
				'id' => 'P1',
			],
			'value' => [
				'type' => 'novalue',
			],
		];
	}

}
