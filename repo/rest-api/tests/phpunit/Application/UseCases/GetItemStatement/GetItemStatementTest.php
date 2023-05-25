<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemStatement;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementTest extends TestCase {

	/**
	 * @var Stub|GetLatestItemRevisionMetadata
	 */
	private $getRevisionMetadata;

	/**
	 * @var Stub|ItemStatementRetriever
	 */
	private $statementRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
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

		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $revision, $lastModified ] );

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

	public function testGivenInvalidStatementId_throwsUseCaseError(): void {
		$statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame(
				"Not a valid statement ID: {$statementId}",
				$e->getErrorMessage()
			);
		}
	}

	public function testItemForStatementSubjectNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$expectedException = $this->createStub( UseCaseException::class );
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetItemStatementRequest( $statementId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testRequestedItemIdNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		// using a different item id below on purpose to check that the *requested* item is being checked, if provided
		$statementId = 'Q321' . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId, $itemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testStatementNotFound_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q321' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revision, $lastModified ] );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	public function testStatementIdNotMatchingItemId_throwsUseCaseError(): void {
		$requestedItemId = new ItemId( 'Q123' );
		$statementItemId = new ItemId( 'Q321' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = $statementItemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revision, $lastModified ] );

		$this->statementRetriever = $this->createMock( ItemStatementRetriever::class );
		$this->statementRetriever->expects( $this->never() )->method( $this->anything() );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( $statementId, $requestedItemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetItemStatement {
		return new GetItemStatement(
			new GetItemStatementValidator(
				new StatementIdValidator( new ItemIdParser() ),
				new ItemIdValidator()
			),
			$this->statementRetriever,
			$this->getRevisionMetadata
		);
	}

}
