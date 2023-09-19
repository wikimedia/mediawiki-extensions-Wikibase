<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementTest extends TestCase {

	use EditMetadataHelper;

	private ReplaceItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ReplaceStatement $replaceStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->replaceStatement  = $this->createStub( ReplaceStatement::class );
	}

	public function testGivenValidReplaceItemStatementRequest_callsReplaceStatementUseCase(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;
		$request = $this->newUseCaseRequest( [
			'$itemId' => (string)$itemId,
			'$statementId' => (string)$statementId,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
		] );

		$expectedResponse = $this->createStub( ReplaceStatementResponse::class );
		$this->replaceStatement  = $this->createMock( ReplaceStatement::class );
		$this->replaceStatement->expects( $this->once() )
			->method( 'execute' )
			->with( $request )
			->willReturn( $expectedResponse );

		$this->assertSame( $expectedResponse, $this->newUseCase()->execute( $request ) );
	}

	public function testGivenInvalidReplaceItemStatementRequest_throws(): void {
		$useCaseRequest = $this->createStub( ReplaceItemStatementRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->validator = $this->createMock( ReplaceItemStatementValidator::class );
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

	public function testGivenStatementIdDoesNotMatchItemId_throws(): void {
		$statementId = 'Q456$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$request = $this->newUseCaseRequest( [
			'$itemId' => 'Q123',
			'$statementId' => $statementId,
		] );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertStringContainsString( $statementId, $e->getErrorMessage() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$request = $this->newUseCaseRequest( [
			'$itemId' => (string)$itemId,
			'$statementId' => (string)$statementId,
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

	public function testGivenReplaceStatementThrows_rethrows(): void {
		$request = $this->newUseCaseRequest( [
			'$itemId' => 'Q123',
			'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
		] );

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->replaceStatement  = $this->createStub( ReplaceStatement::class );
		$this->replaceStatement->method( 'execute' )->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	private function newUseCase(): ReplaceItemStatement {
		return new ReplaceItemStatement(
			$this->validator,
			$this->assertItemExists,
			$this->replaceStatement,
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceItemStatementRequest {
		return new ReplaceItemStatementRequest(
			$requestData['$itemId'],
			$requestData['$statementId'],
			$requestData['$statement'] ?? [
				'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
				'value' => [ 'type' => 'novalue' ],
			],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}
}
