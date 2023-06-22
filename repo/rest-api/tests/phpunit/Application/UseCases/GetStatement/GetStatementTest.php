<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementTest extends TestCase {

	/**
	 * @var Stub|GetLatestStatementSubjectRevisionMetadata
	 */
	private $getRevisionMetadata;

	/**
	 * @var Stub|StatementRetriever
	 */
	private $statementRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->statementRetriever = $this->createStub( StatementRetriever::class );
	}

	public function testGetStatement(): void {
		$itemId = new ItemId( 'Q123' );
		$revision = 987;
		$lastModified = '20201111070707';
		$guidPart = 'c48c32c3-42b5-498f-9586-84608b88747c';
		$statementId = $itemId . StatementGuid::SEPARATOR . $guidPart;
		$expectedStatement = NewStatementReadModel::forProperty( 'P123' )
			->withGuid( $statementId )
			->withValue( 'potato' )
			->build();

		$this->getRevisionMetadata = $this->createMock( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId->getSerialization() )
			->willReturn( [ $revision, $lastModified ] );

		$this->statementRetriever = $this->createMock( StatementRetriever::class );
		$this->statementRetriever->expects( $this->once() )
			->method( 'getStatement' )
			->with( $statementId )
			->willReturn( $expectedStatement );

		$response = $this->newUseCase()->execute(
			new GetStatementRequest( (string)$expectedStatement->getGuid() )
		);

		$this->assertEquals( $expectedStatement, $response->getStatement() );
		$this->assertSame( $revision, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenInvalidStatementId_throwsUseCaseError(): void {
		$statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				new GetStatementRequest( $statementId )
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

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetStatementRequest( $statementId ) );

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

		$this->getRevisionMetadata = $this->createMock( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId->getSerialization() )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetStatementRequest( $statementId, $itemId->getSerialization() )
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

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revision, $lastModified ] );

		try {
			$this->newUseCase()->execute(
				new GetStatementRequest( $statementId )
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

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revision, $lastModified ] );

		$this->statementRetriever = $this->createMock( StatementRetriever::class );
		$this->statementRetriever->expects( $this->never() )->method( $this->anything() );

		try {
			$this->newUseCase()->execute(
				new GetStatementRequest( $statementId, $requestedItemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetStatement {
		return new GetStatement(
			new GetStatementValidator(
				new StatementIdValidator( new ItemIdParser() ),
				new ItemIdValidator()
			),
			$this->statementRetriever,
			$this->getRevisionMetadata
		);
	}

}
