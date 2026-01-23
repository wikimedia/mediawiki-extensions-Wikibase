<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\RouteHandlers;

use Generator;
use LogicException;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Throwable;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\AddItemStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\CreateItemResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItemResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\GetItemAliasesResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\GetItemDescriptionResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\GetItemDescriptionsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\GetItemLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\GetItemLabelsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatementsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\GetPropertyResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliasesResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabelsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\GetPropertyStatementsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelinkResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\GetSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\GetSitelinksResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItemResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchItemAliasesResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabelsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchPropertyResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchSitelinksResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\SetItemDescriptionResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\SetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\SetPropertyLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Item;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelink;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\StatementSubjectRetriever;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\StatementRedirectMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\StatementRedirectMiddlewareFactory;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\Tests\Domains\Crud\Domain\ReadModel\NewStatementReadModel;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RouteHandlersTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private static array $routesData = [];
	private static array $prodRoutesData = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$crudRoutes = fn( $route ) => str_starts_with( $route['path'], '/wikibase/v1/entities' )
			|| str_starts_with( $route['path'], '/wikibase/v1/statements' );
		self::$prodRoutesData = array_filter(
			json_decode( file_get_contents( __DIR__ . '/../../../../../../extension-repo.json' ), true )[ 'RestRoutes' ],
			$crudRoutes
		);
		self::$routesData = array_merge(
			self::$prodRoutesData,
			array_filter(
				json_decode( file_get_contents( __DIR__ . '/../../../../../rest-api/routes.dev.json' ), true ),
				$crudRoutes
			)
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->stubPreconditionMiddlewareFactory();
		$this->stubStatementRedirectMiddlewareFactory();
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testSuccess( array $routeHandler ): void {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		if ( $routeHandler['useCaseResponse'] ) { // "Remove" use cases don't return anything
			$useCase->method( 'execute' )->willReturn( $routeHandler['useCaseResponse'] );
		}
		$this->setService( "WbCrud.{$this->getUseCaseName( $routeHandler['useCase'] )}", $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest(
			self::getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();

		self::assertThat(
			$response->getStatusCode(),
			self::logicalAnd( self::greaterThanOrEqual( 200 ), self::lessThan( 300 ) )
		);
		self::assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testReadWriteAccess( array $routeHandler ): void {
		$routeData = self::getRouteForUseCase( $routeHandler['useCase'] );

		$routeHandler = $this->newHandlerWithValidRequest( $routeData, $routeHandler['validRequest'] );

		self::assertTrue( $routeHandler->needsReadAccess() );
		self::assertSame( $routeData['method'] !== 'GET', $routeHandler->needsWriteAccess() );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesUnexpectedErrors( array $routeHandler ): void {
		$response = $this->getHttpResponseForThrowingUseCase( $routeHandler, new RuntimeException() );

		self::assertSame( UnexpectedErrorHandlerMiddleware::ERROR_CODE, json_decode( $response->getBody()->getContents() )->code );
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesExpectedExceptions( array $routeHandler ): void {
		foreach ( $routeHandler['expectedExceptions'] as [ $error, $assertExpectedResponse ] ) {
			$assertExpectedResponse( $this->getHttpResponseForThrowingUseCase( $routeHandler, $error ) );
		}
	}

	public static function routeHandlersProvider(): Generator {
		$lastModified = '20230731042031';
		$hasHttpStatus = fn( int $status ) => fn( Response $r ) => self::assertSame( $status, $r->getStatusCode() );
		$hasErrorCode = fn( string $errorCode ) => function ( Response $response ) use ( $errorCode ): void {
			self::assertSame( $errorCode, json_decode( (string)$response->getBody() )->code );
			self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		}; // phpcs:ignore -- phpcs doesn't like the semicolon here, but it's very much needed.

		// phpcs:disable Generic.Arrays.ArrayIndent.CloseBraceNotNewLine
		yield 'AddItemStatement' => [ [
			'useCase' => AddItemStatement::class,
			'useCaseResponse' => new AddItemStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'statement' => self::noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'AddPropertyStatement' => [ [
			'useCase' => AddPropertyStatement::class,
			'useCaseResponse' => new AddPropertyStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'statement' => self::noValueStatementSerialization() ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'GetItemAliasesInLanguage' => [ [
			'useCase' => GetItemAliasesInLanguage::class,
			'useCaseResponse' => new GetItemAliasesInLanguageResponse(
				new AliasesInLanguage( 'en', [] ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'aliases' ),
					$hasErrorCode ( UseCaseError::RESOURCE_NOT_FOUND ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemAliases' => [ [
			'useCase' => GetItemAliases::class,
			'useCaseResponse' => new GetItemAliasesResponse( new Aliases(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemDescription' => [ [
			'useCase' => GetItemDescription::class,
			'useCaseResponse' => new GetItemDescriptionResponse(
				new Description( 'en', 'root vegetable' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemDescriptionWithFallback' => [ [
			'useCase' => GetItemDescriptionWithFallback::class,
			'useCaseResponse' => new GetItemDescriptionWithFallbackResponse(
				new Description( 'en', 'root vegetable' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemDescriptions' => [ [
			'useCase' => GetItemDescriptions::class,
			'useCaseResponse' => new GetItemDescriptionsResponse( new Descriptions(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemLabel' => [ [
			'useCase' => GetItemLabel::class,
			'useCaseResponse' => new GetItemLabelResponse(
				new Label( 'en', 'potato' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemLabelWithFallback' => [ [
			'useCase' => GetItemLabelWithFallback::class,
			'useCaseResponse' => new GetItemLabelWithFallbackResponse(
				new Label( 'en', 'potato' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemLabels' => [ [
			'useCase' => GetItemLabels::class,
			'useCaseResponse' => new GetItemLabelsResponse( new Labels(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItem' => [ [
			'useCase' => GetItem::class,
			'useCaseResponse' => new GetItemResponse(
				( new ItemPartsBuilder( new ItemId( 'Q1' ), [] ) )->build(),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetSitelink' => [ [
			'useCase' => GetSitelink::class,
			'useCaseResponse' => new GetSitelinkResponse(
				new Sitelink( 'dewiki', 'Kartoffel', [], 'https://de.wikipedia.org/wiki/Kartoffel' ), $lastModified, 123
			),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'site_id' => 'dewiki' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetSitelinks' => [ [
			'useCase' => GetSitelinks::class,
			'useCaseResponse' => new GetSitelinksResponse( new Sitelinks(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemStatement' => [ [
			'useCase' => GetItemStatement::class,
			'useCaseResponse' => new GetStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'GetItemStatements' => [ [
			'useCase' => GetItemStatements::class,
			'useCaseResponse' => new GetItemStatementsResponse( new StatementList(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetPropertyAliases' => [ [
			'useCase' => GetPropertyAliases::class,
			'useCaseResponse' => new GetPropertyAliasesResponse( new Aliases(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyAliasesInLanguage' => [ [
			'useCase' => GetPropertyAliasesInLanguage::class,
			'useCaseResponse' => new GetPropertyAliasesInLanguageResponse(
				new AliasesInLanguage( 'en', [ 'is a', 'example of' ] ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyDescription' => [ [
			'useCase' => GetPropertyDescription::class,
			'useCaseResponse' => new GetPropertyDescriptionResponse(
				new Description( 'en', 'property description' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyDescriptionWithFallback' => [ [
			'useCase' => GetPropertyDescriptionWithFallback::class,
			'useCaseResponse' => new GetPropertyDescriptionWithFallbackResponse(
				new Description( 'en', 'property description' ),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyDescriptions' => [ [
			'useCase' => GetPropertyDescriptions::class,
			'useCaseResponse' => new GetPropertyDescriptionsResponse( new Descriptions(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyLabel' => [ [
			'useCase' => GetPropertyLabel::class,
			'useCaseResponse' => new GetPropertyLabelResponse( new Label( 'en', 'instance of' ), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyLabelWithFallback' => [ [
			'useCase' => GetPropertyLabelWithFallback::class,
			'useCaseResponse' => new GetPropertyLabelWithFallbackResponse( new Label( 'en', 'instance of' ), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyLabels' => [ [
			'useCase' => GetPropertyLabels::class,
			'useCaseResponse' => new GetPropertyLabelsResponse( new Labels(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetProperty' => [ [
			'useCase' => GetProperty::class,
			'useCaseResponse' => new GetPropertyResponse(
				( new PropertyPartsBuilder( new NumericPropertyId( 'P1' ), [] ) )->build(),
				$lastModified,
				123
			),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyStatement' => [ [
			'useCase' => GetPropertyStatement::class,
			'useCaseResponse' => new GetStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'GetPropertyStatements' => [ [
			'useCase' => GetPropertyStatements::class,
			'useCaseResponse' => new GetPropertyStatementsResponse( new StatementList(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'GetStatement' => [ [
			'useCase' => GetStatement::class,
			'useCaseResponse' => new GetStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'PatchItemLabels' => [ [
			'useCase' => PatchItemLabels::class,
			'useCaseResponse' => new PatchItemLabelsResponse( new Labels(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/en' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'PatchItemDescriptions' => [ [
			'useCase' => PatchItemDescriptions::class,
			'useCaseResponse' => new PatchItemDescriptionsResponse( new Descriptions(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/en' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'PatchItemAliases' => [ [
			'useCase' => PatchItemAliases::class,
			'useCaseResponse' => new PatchItemAliasesResponse( new Aliases(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/fr' ] ] ],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'item' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'PatchItemStatement' => [ [
			'useCase' => PatchItemStatement::class,
			'useCaseResponse' => new PatchStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'PatchPropertyStatement' => [ [
			'useCase' => PatchPropertyStatement::class,
			'useCaseResponse' => new PatchStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'PatchStatement' => [ [
			'useCase' => PatchStatement::class,
			'useCaseResponse' => new PatchStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'RemoveItemLabel' => [ [
			'useCase' => RemoveItemLabel::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'RemovePropertyLabel' => [ [
			'useCase' => RemovePropertyLabel::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'property' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
			],
		] ];
		yield 'RemoveItemDescription' => [ [
			'useCase' => RemoveItemDescription::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'RemovePropertyDescription' => [ [
			'useCase' => RemovePropertyDescription::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'property' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
			],
		] ];
		yield 'RemoveItemStatement' => [ [
			'useCase' => RemoveItemStatement::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'RemovePropertyStatement' => [ [
			'useCase' => RemovePropertyStatement::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'RemoveStatement' => [ [
			'useCase' => RemoveStatement::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'ReplaceItemStatement' => [ [
			'useCase' => ReplaceItemStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => self::noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'ReplacePropertyStatement' => [ [
			'useCase' => ReplacePropertyStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => self::noValueStatementSerialization() ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'ReplaceStatement' => [ [
			'useCase' => ReplaceStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( self::noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => self::noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'statement_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ) ],
			],
		] ];
		yield 'SetItemLabel' => [ [
			'useCase' => SetItemLabel::class,
			'useCaseResponse' => new SetItemLabelResponse(
				new Label( 'en', 'potato' ),
				$lastModified,
				123,
				true
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [ 'label' => 'potato' ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'SetItemDescription' => [ [
			'useCase' => SetItemDescription::class,
			'useCaseResponse' => new SetItemDescriptionResponse(
				new Description( 'en', 'root vegetable' ),
				$lastModified,
				123,
				true
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [ 'description' => 'root vegetable' ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'SetPropertyLabel' => [ [
			'useCase' => SetPropertyLabel::class,
			'useCaseResponse' => new SetPropertyLabelResponse(
				new Label( 'en', 'instance of' ),
				$lastModified,
				123,
				true
			),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ],
				'bodyContents' => [ 'label' => 'instance of' ],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'property' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
			],
		] ];
		yield 'SetPropertyDescription' => [ [
			'useCase' => SetPropertyDescription::class,
			'useCaseResponse' => new SetPropertyDescriptionResponse(
				new Description( 'en', 'that class of which this subject is a particular example and member' ),
				$lastModified,
				123,
				true
			),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ],
				'bodyContents' => [ 'description' => 'that class of which this subject is a particular example and member' ],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'property' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
			],
		] ];
		yield 'PatchPropertyAliases' => [ [
			'useCase' => PatchPropertyAliases::class,
			'useCaseResponse' => new PatchPropertyAliasesResponse( new Aliases(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/fr' ] ] ],
			],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'PatchPropertyLabels' => [ [
			'useCase' => PatchPropertyLabels::class,
			'useCaseResponse' => new PatchPropertyLabelsResponse( new Labels(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/fr' ] ] ],
			],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'AddItemAliasesInLanguage' => [ [
			'useCase' => AddItemAliasesInLanguage::class,
			'useCaseResponse' => new AddItemAliasesInLanguageResponse(
				new AliasesInLanguage( 'en', [] ),
				$lastModified,
				123,
				true
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'language_code' => 'en' ],
				'bodyContents' => [ 'aliases' => [ 'spud', 'tater' ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'AddPropertyAliasesInLanguage' => [ [
			'useCase' => AddPropertyAliasesInLanguage::class,
			'useCaseResponse' => new AddPropertyAliasesInLanguageResponse(
				new AliasesInLanguage( 'en', [] ),
				true,
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ],
				'bodyContents' => [ 'aliases' => [ 'alias-1', 'alias-2' ] ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'property_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'PatchPropertyDescriptions' => [ [
			'useCase' => PatchPropertyDescriptions::class,
			'useCaseResponse' => new PatchPropertyDescriptionsResponse( new Descriptions(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/en' ] ] ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'property_id' ] ),
				$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
			] ],
		] ];
		yield 'RemoveSitelink' => [ [
			'useCase' => RemoveSitelink::class,
			'useCaseResponse' => null,
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'site_id' => 'dewiki' ],
				'bodyContents' => [],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'SetSitelink' => [ [
			'useCase' => SetSitelink::class,
			'useCaseResponse' => new SetSitelinkResponse(
				new Sitelink( 'dewiki', 'title', [], '' ),
				$lastModified,
				123,
				false
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'site_id' => 'dewiki' ],
				'bodyContents' => [ 'sitelink' => [ 'title' => 'title' ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'PatchSitelinks' => [ [
			'useCase' => PatchSitelinks::class,
			'useCaseResponse' => new PatchSitelinksResponse(
				new Sitelinks( new Sitelink( 'dewiki', 'title', [], '' ) ),
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/dewiki/badges' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_PATH_PARAMETER, '', [ 'parameter' => 'item_id' ] ),
					$hasErrorCode ( UseCaseError::INVALID_PATH_PARAMETER ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'CreateItem' => [ [
			'useCase' => CreateItem::class,
			'useCaseResponse' => new CreateItemResponse(
				new Item(
					new ItemId( 'Q123' ),
					new Labels( new Label( 'en', 'new Item' ) ),
					new Descriptions(),
					new Aliases(),
					new Sitelinks(),
					new StatementList()
				),
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [],
				'bodyContents' => [ 'item' => [ 'labels' => [ 'en' => 'new Item' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError(
						UseCaseError::INVALID_VALUE,
						"Invalid value at '/item/labels/en'",
						[ UseCaseError::CONTEXT_PATH => '/item/labels/en' ]
					),
					$hasErrorCode ( UseCaseError::INVALID_VALUE ),
				],
			],
		] ];
		yield 'CreateProperty' => [ [
			'useCase' => CreateProperty::class,
			'useCaseResponse' => new CreatePropertyResponse(
				new Property(
					new NumericPropertyId( 'P123' ),
					'string',
					new Labels(),
					new Descriptions(),
					new Aliases(),
					new StatementList()
				),
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [],
				'bodyContents' => [ 'property' => [ 'data_type' => 'string' ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError(
						UseCaseError::INVALID_VALUE,
						"Invalid value at '/property/labels'",
						[ UseCaseError::CONTEXT_PATH => '/item/labels' ]
					),
					$hasErrorCode ( UseCaseError::INVALID_VALUE ),
				],
			],
		] ];
		yield 'PatchProperty' => [ [
			'useCase' => PatchProperty::class,
			'useCaseResponse' => new PatchPropertyResponse(
				new Property(
					new NumericPropertyId( 'P1' ),
					'string',
					new Labels( new Label( 'en', 'new Property' ) ),
					new Descriptions(),
					new Aliases(),
					new StatementList()
				),
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'add', 'path' => '/labels/en', 'value' => 'new Property' ] ] ],
			],
			'expectedExceptions' => [ [
				UseCaseError::newResourceNotFound( 'property' ),
				$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
			] ],
		] ];
		yield 'PatchItem' => [ [
			'useCase' => PatchItem::class,
			'useCaseResponse' => new PatchItemResponse(
				new Item(
					new ItemId( 'Q1' ),
					new Labels( new Label( 'en', 'new Item' ) ),
					new Descriptions(),
					new Aliases(),
					new Sitelinks(),
					new StatementList()
				),
				$lastModified,
				123
			),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'add', 'path' => '/labels/en', 'value' => 'new Item' ] ] ],
			],
			'expectedExceptions' => [
				[
					UseCaseError::newResourceNotFound( 'item' ),
					$hasErrorCode( UseCaseError::RESOURCE_NOT_FOUND ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
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
				if ( $route === self::getRouteForUseCase( $routeTestData[0]['useCase'] ) ) {
					continue 2;
				}
			}
			self::fail( "Route handler {$route['factory']} is not covered by any tests" );
		}
	}

	private function getHttpResponseForThrowingUseCase( array $routeHandler, Throwable $error ): Response {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willThrowException( $error );

		$this->setService( "WbCrud.{$this->getUseCaseName( $routeHandler['useCase'] )}", $useCase );
		$this->setService( 'WbCrud.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		return $this->newHandlerWithValidRequest(
			self::getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();
	}

	private function newHandlerWithValidRequest( array $routeData, array $validRequest ): Handler {
		$routeHandler = $routeData['factory']();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => $routeData['method'],
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => $validRequest['pathParams'],
				'bodyContents' => isset( $validRequest['bodyContents'] )
					? json_encode( $validRequest['bodyContents'] ) : null,
			] ),
			[ 'path' => $routeData['path'] ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

	private static function getRouteForUseCase( string $useCaseClass ): array {
		$useCaseName = self::getUseCaseName( $useCaseClass );
		foreach ( self::$routesData as $route ) {
			if ( strpos( $route['factory'], "\\{$useCaseName}RouteHandler" ) ) {
				return $route;
			}
		}

		throw new LogicException( "No route found for use case $useCaseName" );
	}

	private static function getUseCaseName( string $fqn ): string {
		$classNameParts = explode( '\\', $fqn );
		return $classNameParts[ count( $classNameParts ) - 1 ];
	}

	private static function noValueStatementSerialization(): array {
		return [
			'property' => [
				'id' => 'P1',
			],
			'value' => [
				'type' => 'novalue',
			],
		];
	}

	private static function noValueStatementReadModel(): Statement {
		return NewStatementReadModel::noValueFor( 'P1' )
			->withGuid( 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
	}

	/**
	 * Overrides the PreconditionMiddlewareFactory service with one that doesn't need the database.
	 */
	private function stubPreconditionMiddlewareFactory(): void {
		$entityRevLookup = $this->createStub( EntityRevisionLookup::class );
		$entityRevLookup->method( 'getLatestRevisionId' )->willReturn( LatestRevisionIdResult::nonexistentEntity() );
		$preconditionMiddlewareFactory = new PreconditionMiddlewareFactory(
			$entityRevLookup,
			new BasicEntityIdParser(),
			new ConditionalHeaderUtil()
		);
		$this->setService( 'WbCrud.PreconditionMiddlewareFactory', $preconditionMiddlewareFactory );
	}

	private function stubStatementRedirectMiddlewareFactory(): void {
		$statementSubject = $this->createStub( StatementListProvidingEntity::class );
		$statementSubject->method( 'getStatements' )->willReturn( new StatementList() );

		$entityRevLookup = $this->createStub( EntityRevisionLookup::class );
		$entityRevLookup->method( 'getEntityRevision' )->willReturn( new EntityRevision( $statementSubject ) );

		$middleware = new StatementRedirectMiddleware(
			WikibaseRepo::getEntityIdParser(),
			new StatementSubjectRetriever( $entityRevLookup ),
			'statement_id',
			null
		);

		$factory = $this->createStub( StatementRedirectMiddlewareFactory::class );
		$factory->method( 'newStatementRedirectMiddleware' )->willReturn( $middleware );
		$this->setService( 'WbCrud.StatementRedirectMiddlewareFactory', $factory );
	}
}
