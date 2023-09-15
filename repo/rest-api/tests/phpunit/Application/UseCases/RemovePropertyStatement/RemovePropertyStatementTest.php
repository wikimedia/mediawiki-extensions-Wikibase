<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemovePropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation\TestValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemovePropertyStatementTest extends TestCase {

	use EditMetadataHelper;

	private AssertPropertyExists $assertPropertyExists;
	private RemoveStatement $removeStatement;
	private RemovePropertyStatementValidator $removePropertyStatementValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->removeStatement  = $this->createStub( RemoveStatement::class );
		$this->removePropertyStatementValidator = new RemovePropertyStatementValidator(
			new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() )
		);
	}

	public function testGivenValidRemovePropertyStatementRequest_callsRemoveStatementUseCase(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$statementId = new StatementGuid( $propertyId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$newStatementSerialization = [ 'some' => 'statement' ];
		$editTags = [ TestValidatingRequestFieldDeserializerFactory::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'statement removed by ' . __method__;

		$request = $this->newUseCaseRequest( [
			'$propertyId' => (string)$propertyId,
			'$statementId' => (string)$statementId,
			'$statement' => $newStatementSerialization,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$this->removeStatement = $this->createMock( RemoveStatement::class );
		$this->removeStatement->expects( $this->once() )
			->method( 'execute' )
			->with( $request );

		$this->newUseCase()->execute( $request );
	}

	public function testGivenInvalidRemovePropertyStatementRequest_throws(): void {
		$useCaseRequest = $this->createStub( RemovePropertyStatementRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->removePropertyStatementValidator = $this->createMock( RemovePropertyStatementValidator::class );
		$this->removePropertyStatementValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $useCaseRequest )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $useCaseRequest );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	public function testGivenStatementIdDoesNotMatchPropertyId_throws(): void {
		$statementId = 'P456$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$request = $this->createStub( RemovePropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getStatementId' )->willReturn( $statementId );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertStringContainsString( $statementId, $e->getErrorMessage() );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$statementId = new StatementGuid( $propertyId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editTags = [ TestValidatingRequestFieldDeserializerFactory::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'statement removed by ' . __method__;
		$request = $this->newUseCaseRequest( [
			'$propertyId' => (string)$propertyId,
			'$statementId' => (string)$statementId,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertPropertyExists = $this->createMock( AssertPropertyExists::class );
		$this->assertPropertyExists->expects( $this->once() )
			->method( 'execute' )
			->with( $propertyId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenRemoveStatementThrows_rethrows(): void {
		$request = $this->createStub( RemovePropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getStatementId' )->willReturn( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->removeStatement  = $this->createStub( RemoveStatement::class );
		$this->removeStatement->method( 'execute' )->willThrowException( $expectedUseCaseError );
		$this->removeStatement->method( 'execute' )->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	private function newUseCase(): RemovePropertyStatement {
		return new RemovePropertyStatement(
			$this->assertPropertyExists,
			$this->removeStatement,
			$this->removePropertyStatementValidator
		);
	}

	private function newUseCaseRequest( array $requestData ): RemovePropertyStatementRequest {
		return new RemovePropertyStatementRequest(
			$requestData['$propertyId'],
			$requestData['$statementId'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

}
