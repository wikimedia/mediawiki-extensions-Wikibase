<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatement;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementTest extends TestCase {

	/**
	 * @var Stub|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var Stub|ItemStatementRetriever
	 */
	private $statementRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->statementRetriever = $this->createStub( ItemStatementRetriever::class );
	}

	public function testGetItemStatement(): void {
		$itemId = new ItemId( 'Q123' );
		$revision = 987;
		$lastModified = '20201111070707';
		$guidPart = 'c48c32c3-42b5-498f-9586-84608b88747c';
		$statementId = $itemId . StatementGuid::SEPARATOR . $guidPart;
		$expectedStatement = NewStatementReadModel::forProperty( 'P123' )
			->withGuid( $statementId )
			->withValue( 'potato' )
			->build();

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revision, $lastModified ) );

		$this->statementRetriever = $this->createMock( ItemStatementRetriever::class );
		$this->statementRetriever->expects( $this->once() )
			->method( 'getStatement' )
			->with( $statementId )
			->willReturn( $expectedStatement );

		$response = $this->newUseCase()->execute(
			new GetItemStatementRequest( (string)$expectedStatement->getGuid() )
		);

		$this->assertEquals( $expectedStatement, $response->getStatement() );
		$this->assertSame( $revision, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenInvalidStatementId_throwsUseCaseException(): void {
		$statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame(
				"Not a valid statement ID: {$statementId}",
				$e->getErrorMessage()
			);
		}
	}

	public function testItemForStatementIdNotFound_throwsUseCaseException(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->itemRevisionMetadataRetriever = $this->createMock(
			ItemRevisionMetadataRetriever::class
		);
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newUseCase()->execute( new GetItemStatementRequest( $statementId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	public function testRequestedItemIdNotFound_throwsUseCaseException(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->itemRevisionMetadataRetriever = $this->createMock(
			ItemRevisionMetadataRetriever::class
		);
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId, $itemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $e->getErrorCode() );
		}
	}

	public function testStatementNotFound_throwsUseCaseException(): void {
		$itemId = new ItemId( 'Q321' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->itemRevisionMetadataRetriever = $this->createMock(
			ItemRevisionMetadataRetriever::class
		);
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn(
				LatestItemRevisionMetadataResult::concreteRevision( $revision, $lastModified )
			);

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	public function testStatementIdNotMatchingItemId_throwsUseCaseException(): void {
		$requestedItemId = new ItemId( 'Q123' );
		$statementItemId = new ItemId( 'Q321' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = $statementItemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->itemRevisionMetadataRetriever = $this->createMock(
			ItemRevisionMetadataRetriever::class
		);
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $requestedItemId )
			->willReturn(
				LatestItemRevisionMetadataResult::concreteRevision( $revision, $lastModified )
			);
		$this->statementRetriever = $this->createMock( ItemStatementRetriever::class );
		$this->statementRetriever->expects( $this->never() )->method( $this->anything() );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId, $requestedItemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetItemStatement {
		return new GetItemStatement(
			new GetItemStatementValidator(
				new StatementIdValidator( new ItemIdParser() ),
				new ItemIdValidator()
			),
			$this->statementRetriever,
			$this->itemRevisionMetadataRetriever
		);
	}

}
