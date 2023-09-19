<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyStatementTest extends TestCase {

	use EditMetadataHelper;

	private PatchPropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private PatchStatement $patchStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->patchStatement  = $this->createStub( PatchStatement::class );
	}

	public function testGivenValidRequest_callsPatchStatementUseCase(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$statementId = new StatementGuid( $propertyId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$patch = $this->getValidValueReplacingPatch( 'new statement value' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$request = $this->newUseCaseRequest( [
			'$propertyId' => (string)$propertyId,
			'$statementId' => (string)$statementId,
			'$patch' => $patch,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$expectedResponse = $this->createStub( PatchStatementResponse::class );
		$this->patchStatement  = $this->createMock( PatchStatement::class );
		$this->patchStatement->expects( $this->once() )
			->method( 'execute' )
			->with( $request )
			->willReturn( $expectedResponse );

		$this->assertSame( $expectedResponse, $this->newUseCase()->execute( $request ) );
	}

	public function testGivenPatchStatementThrows_rethrows(): void {
		$request = $this->createStub( PatchPropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getStatementId' )->willReturn( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->patchStatement  = $this->createStub( PatchStatement::class );
		$this->patchStatement->method( 'execute' )->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	public function testGivenInvalidRequest_throwsUseCaseError(): void {
		$useCaseRequest = $this->createStub( PatchPropertyStatementRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->validator = $this->createMock( PatchPropertyStatementValidator::class );
		$this->validator->expects( $this->once() )
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

	public function testGivenPropertyNotFound_throwsUseCaseError(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$statementId = new StatementGuid( $propertyId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$patch = $this->getValidValueReplacingPatch( 'new statement value' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$request = $this->newUseCaseRequest( [
			'$propertyId' => (string)$propertyId,
			'$statementId' => (string)$statementId,
			'$patch' => $patch,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertPropertyExists = $this->createMock( AssertPropertyExists::class );
		$this->assertPropertyExists->expects( $this->once() )
			->method( 'execute' )
			->with( $propertyId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenStatementIdDoesNotMatchPropertyId_throws(): void {
		$statementId = 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$request = $this->createStub( PatchPropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P2' );
		$request->method( 'getStatementId' )->willReturn( $statementId );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertStringContainsString( $statementId, $e->getErrorMessage() );
		}
	}

	private function newUseCase(): PatchPropertyStatement {
		return new PatchPropertyStatement(
			$this->validator,
			$this->assertPropertyExists,
			$this->patchStatement,
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchPropertyStatementRequest {
		return new PatchPropertyStatementRequest(
			$requestData['$propertyId'],
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

	private function getValidValueReplacingPatch( string $newStatementValue = '' ): array {
		return [
			[
				'op' => 'replace',
				'path' => '/value/content',
				'value' => $newStatementValue,
			],
		];
	}

}
