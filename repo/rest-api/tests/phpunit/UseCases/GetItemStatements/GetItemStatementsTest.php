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
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRedirectResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
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
		$statement1PropertyId = 'P123';
		$statement1Value = 'potato';
		$statement2PropertyId = 'P321';
		$statement2Value = 'banana';
		$statements = new StatementList(
			NewStatement::forProperty( $statement1PropertyId )
				->withValue( $statement1Value )
				->build(),
			NewStatement::forProperty( $statement2PropertyId )
				->withValue( $statement2Value )
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

		$serializedStatements = $response->getStatements();
		$this->assertArrayHasKey( $statement1PropertyId, $serializedStatements );
		$this->assertSame( $statement1Value, $serializedStatements[$statement1PropertyId][0]['mainsnak']['datavalue']['value'] );
		$this->assertArrayHasKey( $statement2PropertyId, $serializedStatements );
		$this->assertSame( $statement2Value, $serializedStatements[$statement2PropertyId][0]['mainsnak']['datavalue']['value'] );

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

		$this->assertInstanceOf( GetItemStatementsRedirectResponse::class, $response );
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
