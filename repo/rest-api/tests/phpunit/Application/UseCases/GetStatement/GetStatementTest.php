<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation\TestValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementTest extends TestCase {

	private GetStatementValidator $requestValidator;
	private StatementRetriever $statementRetriever;
	private GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata;

	protected function setUp(): void {
		parent::setUp();

		$this->requestValidator = new GetStatementValidator(
			new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() )
		);
		$this->statementRetriever = $this->createStub( StatementRetriever::class );
		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
	}

	public function testGetStatement(): void {
		$revision = 987;
		$lastModified = '20201111070707';
		$guidPart = 'c48c32c3-42b5-498f-9586-84608b88747c';
		$statementId = new StatementGuid( new ItemId( 'Q123' ), $guidPart );
		$expectedStatement = NewStatementReadModel::forProperty( 'P123' )
			->withGuid( (string)$statementId )
			->withValue( 'potato' )
			->build();

		$this->getRevisionMetadata = $this->createMock( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $statementId )
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

	public function testGivenInvalidRequest_throwsUseCaseError(): void {
		$request = $this->createStub( GetStatementRequest::class );
		$useCaseError = $this->createStub( UseCaseError::class );

		$this->requestValidator = $this->createMock( GetStatementValidator::class );
		$this->requestValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willThrowException( $useCaseError );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $useCaseError, $e );
		}
	}

	public function testStatementSubjectNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetStatementRequest( $statementId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testStatementNotFound_throwsUseCaseError(): void {
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revision, $lastModified ] );

		try {
			$this->newUseCase()->execute( new GetStatementRequest( $statementId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetStatement {
		return new GetStatement(
			$this->requestValidator,
			$this->statementRetriever,
			$this->getRevisionMetadata
		);
	}

}
