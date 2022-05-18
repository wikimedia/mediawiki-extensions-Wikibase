<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatements;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsTest extends TestCase {

	/**
	 * @var Stub|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var Stub|ItemStatementsRetriever
	 */
	private $statementsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->statementsRetriever = $this->createStub( ItemStatementsRetriever::class );
	}

	public function testGetItemStatements(): void {
		$itemId = new ItemId( 'Q123' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statements = new StatementList(
			NewStatement::forProperty( 'P123' )
				->withValue( 'potato' )
				->build(),
			NewStatement::forProperty( 'P321' )
				->withValue( 'banana' )
				->build()
		);

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revision, $lastModified ) );

		$this->statementsRetriever = $this->createMock( ItemStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $itemId )
			->willReturn( $statements );

		$response = $this->newUseCase()->execute(
			new GetItemStatementsRequest( $itemId->getSerialization() )
		);

		$this->assertSame( $statements, $response->getStatements() );
		$this->assertSame( $revision, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenInvalidItemId_returnsErrorResponse(): void {
		$response = $this->newUseCase()->execute(
			new GetItemStatementsRequest( 'X321' )
		);

		$this->assertInstanceOf( GetItemStatementsErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $response->getCode() );
	}

	public function testItemNotFound_returnsErrorResponse(): void {
		$itemId = "Q123";

		$this->itemRevisionMetadataRetriever->method( "getLatestRevisionMetadata" )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );
		$itemStatementsRequest = new GetItemStatementsRequest( $itemId );
		$itemStatementsResponse = $this->newUseCase()->execute( $itemStatementsRequest );
		$this->assertInstanceOf( GetItemStatementsErrorResponse::class, $itemStatementsResponse );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $itemStatementsResponse->getCode() );
	}

	public function testGivenItemRedirect_returnsRedirectResponse(): void {
		$redirectSource = 'Q123';
		$redirectTarget = 'Q321';

		$this->itemRevisionMetadataRetriever
			->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		$response = $this->newUseCase()->execute( new GetItemStatementsRequest( $redirectSource ) );

		$this->assertInstanceOf( ItemRedirectResponse::class, $response );
		$this->assertSame( $redirectTarget, $response->getRedirectTargetId() );
	}

	private function newUseCase(): GetItemStatements {
		return new GetItemStatements(
			new GetItemStatementsValidator( new ItemIdValidator() ),
			$this->statementsRetriever,
			$this->itemRevisionMetadataRetriever,
			WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementListSerializer()
		);
	}

}
