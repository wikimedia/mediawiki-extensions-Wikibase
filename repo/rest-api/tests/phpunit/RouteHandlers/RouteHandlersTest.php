<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

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
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

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
	public function testSuccess( array $routeHandler ): void {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		if ( $routeHandler['useCaseResponse'] ) { // "Remove" use cases don't return anything
			$useCase->method( 'execute' )->willReturn( $routeHandler['useCaseResponse'] );
		}
		$this->setService( "WbRestApi.{$this->getUseCaseName( $routeHandler['useCase'] )}", $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();

		$this->assertThat(
			$response->getStatusCode(),
			$this->logicalAnd( $this->greaterThanOrEqual( 200 ), $this->lessThan( 300 ) )
		);
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
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
		$response = $this->getHttpResponseForThrowingUseCase( $routeHandler, new RuntimeException() );

		$this->assertSame( UseCaseError::UNEXPECTED_ERROR, json_decode( $response->getBody()->getContents() )->code );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesExpectedExceptions( array $routeHandler ): void {
		foreach ( $routeHandler['expectedExceptions'] as [ $error, $assertExpectedResponse ] ) {
			$assertExpectedResponse( $this->getHttpResponseForThrowingUseCase( $routeHandler, $error ) );
		}
	}

	public function routeHandlersProvider(): Generator {
		$lastModified = '20230731042031';
		$hasHttpStatus = fn( int $status ) => fn( Response $r ) => $this->assertSame( $status, $r->getStatusCode() );
		$hasErrorCode = fn( string $errorCode ) => function ( Response $response ) use ( $errorCode ): void {
			$this->assertSame( $errorCode, json_decode( $response->getBody()->getContents() )->code );
			$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		}; // phpcs:ignore -- phpcs doesn't like the semicolon here, but it's very much needed.

		// phpcs:disable Generic.Arrays.ArrayIndent.CloseBraceNotNewLine
		yield 'AddItemStatement' => [ [
			'useCase' => AddItemStatement::class,
			'useCaseResponse' => new AddItemStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'AddPropertyStatement' => [ [
			'useCase' => AddPropertyStatement::class,
			'useCaseResponse' => new AddPropertyStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
				$hasErrorCode( UseCaseError::INVALID_STATEMENT_ID ),
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
					new UseCaseError( UseCaseError::ALIASES_NOT_DEFINED, '' ),
					$hasErrorCode ( UseCaseError::ALIASES_NOT_DEFINED ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetItemStatement' => [ [
			'useCase' => GetItemStatement::class,
			'useCaseResponse' => new GetStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
			],
		] ];
		yield 'GetItemStatements' => [ [
			'useCase' => GetItemStatements::class,
			'useCaseResponse' => new GetItemStatementsResponse( new StatementList(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'item_id' => 'Q1' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasHttpStatus( 308 ) ],
			],
		] ];
		yield 'GetPropertyAliases' => [ [
			'useCase' => GetPropertyAliases::class,
			'useCaseResponse' => new GetPropertyAliasesResponse( new Aliases(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
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
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
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
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyDescriptions' => [ [
			'useCase' => GetPropertyDescriptions::class,
			'useCaseResponse' => new GetPropertyDescriptionsResponse( new Descriptions(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyLabel' => [ [
			'useCase' => GetPropertyLabel::class,
			'useCaseResponse' => new GetPropertyLabelResponse( new Label( 'en', 'instance of' ), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'language_code' => 'en' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyLabels' => [ [
			'useCase' => GetPropertyLabels::class,
			'useCaseResponse' => new GetPropertyLabelsResponse( new Labels(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
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
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
		] ];
		yield 'GetPropertyStatement' => [ [
			'useCase' => GetPropertyStatement::class,
			'useCaseResponse' => new GetStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
				$hasErrorCode( UseCaseError::INVALID_STATEMENT_ID ),
			] ],
		] ];
		yield 'GetPropertyStatements' => [ [
			'useCase' => GetPropertyStatements::class,
			'useCaseResponse' => new GetPropertyStatementsResponse( new StatementList(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'property_id' => 'P1' ] ],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
		] ];
		yield 'GetStatement' => [ [
			'useCase' => GetStatement::class,
			'useCaseResponse' => new GetStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [ 'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ] ],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::ITEM_REDIRECTED ) ],
			],
		] ];
		yield 'PatchItemStatement' => [ [
			'useCase' => PatchItemStatement::class,
			'useCaseResponse' => new PatchStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
			],
		] ];
		yield 'PatchPropertyStatement' => [ [
			'useCase' => PatchPropertyStatement::class,
			'useCaseResponse' => new PatchStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
				$hasErrorCode( UseCaseError::INVALID_STATEMENT_ID ),
			] ],
		] ];
		yield 'PatchStatement' => [ [
			'useCase' => PatchStatement::class,
			'useCaseResponse' => new PatchStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'patch' => [ [ 'op' => 'remove', 'path' => '/references' ] ] ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
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
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
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
				new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
				$hasErrorCode( UseCaseError::INVALID_STATEMENT_ID ),
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
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
			],
		] ];
		yield 'ReplaceItemStatement' => [ [
			'useCase' => ReplaceItemStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'item_id' => 'Q1', 'statement_id' => 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
			],
		] ];
		yield 'ReplacePropertyStatement' => [ [
			'useCase' => ReplacePropertyStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'property_id' => 'P1', 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
			'expectedExceptions' => [ [
				new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
				$hasErrorCode( UseCaseError::INVALID_STATEMENT_ID ),
			] ],
		] ];
		yield 'ReplaceStatement' => [ [
			'useCase' => ReplaceStatement::class,
			'useCaseResponse' => new ReplaceStatementResponse( $this->noValueStatementReadModel(), $lastModified, 123 ),
			'validRequest' => [
				'pathParams' => [ 'statement_id' => 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ],
				'bodyContents' => [ 'statement' => $this->noValueStatementSerialization() ],
			],
			'expectedExceptions' => [
				[
					new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_STATEMENT_ID ),
				],
				[ new ItemRedirect( 'Q123' ), $hasErrorCode( UseCaseError::STATEMENT_NOT_FOUND ) ],
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::INVALID_ITEM_ID, '' ),
					$hasErrorCode ( UseCaseError::INVALID_ITEM_ID ),
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
					new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
					$hasErrorCode ( UseCaseError::PROPERTY_NOT_FOUND ),
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
					new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
					$hasErrorCode ( UseCaseError::PROPERTY_NOT_FOUND ),
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
				new UseCaseError( UseCaseError::PROPERTY_NOT_FOUND, '' ),
				$hasErrorCode( UseCaseError::PROPERTY_NOT_FOUND ),
			] ],
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

	private function getHttpResponseForThrowingUseCase( array $routeHandler, Throwable $error ): Response {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willThrowException( $error );

		$this->setService( "WbRestApi.{$this->getUseCaseName( $routeHandler['useCase'] )}", $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		return $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();
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
			] ),
			[ 'path' => $routeData['path'] ]
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

	private function noValueStatementReadModel(): Statement {
		return NewStatementReadModel::noValueFor( 'P1' )
			->withGuid( 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
	}

	/**
	 * Overrides the PreconditionMiddlewareFactory service with one that doesn't need the database.
	 */
	private function setMockPreconditionMiddlewareFactory(): void {
		$entityRevLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevLookup->method( 'getLatestRevisionId' )->willReturn( LatestRevisionIdResult::nonexistentEntity() );
		$preconditionMiddlewareFactory = new PreconditionMiddlewareFactory(
			$entityRevLookup,
			new BasicEntityIdParser(),
			new ConditionalHeaderUtil()
		);
		$this->setService( 'WbRestApi.PreconditionMiddlewareFactory', $preconditionMiddlewareFactory );
	}

}
