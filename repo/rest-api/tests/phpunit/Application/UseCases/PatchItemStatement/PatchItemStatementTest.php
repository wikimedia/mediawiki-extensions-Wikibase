<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementTest extends TestCase {

	use EditMetadataHelper;

	private PatchItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private PatchStatement $patchStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->patchStatement  = $this->createStub( PatchStatement::class );
	}

	public function testGivenValidRequest_callsPatchStatementUseCase(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$patch = $this->getValidValueReplacingPatch( 'new statement value' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$request = $this->newUseCaseRequest( [
			'$itemId' => (string)$itemId,
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
		$request = $this->createStub( PatchItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getStatementId' )->willReturn( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

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
		$useCaseRequest = $this->createStub( PatchItemStatementRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->validator = $this->createMock( PatchItemStatementValidator::class );
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

	public function testGivenItemNotFoundOrRedirect_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$patch = $this->getValidValueReplacingPatch( 'new statement value' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$request = $this->newUseCaseRequest( [
			'$itemId' => (string)$itemId,
			'$statementId' => (string)$statementId,
			'$patch' => $patch,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createMock( AssertItemExists::class );
		$this->assertItemExists->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenStatementIdDoesNotMatchItemId_throws(): void {
		$statementId = 'Q456$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$request = $this->createStub( PatchItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getStatementId' )->willReturn( $statementId );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertStringContainsString( $statementId, $e->getErrorMessage() );
		}
	}

	private function newUseCase(): PatchItemStatement {
		return new PatchItemStatement(
			$this->validator,
			$this->assertItemExists,
			$this->patchStatement,
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchItemStatementRequest {
		return new PatchItemStatementRequest(
			$requestData['$itemId'],
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
