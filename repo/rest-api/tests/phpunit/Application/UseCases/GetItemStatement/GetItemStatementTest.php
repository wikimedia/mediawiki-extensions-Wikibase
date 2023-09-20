<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementTest extends TestCase {

	private GetItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private GetStatement $getStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->getStatement = $this->createStub( GetStatement::class );
	}

	public function testGivenValidRequest_callsGetStatementUseCase(): void {
		$expectedResponse = $this->createStub( GetStatementResponse::class );
		$this->getStatement = $this->createStub( GetStatement::class );
		$this->getStatement->method( 'execute' )->willReturn( $expectedResponse );

		$this->assertSame(
			$expectedResponse,
			$this->newUseCase()->execute( new GetItemStatementRequest(
				'Q123',
				'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
			) )
		);
	}

	public function testGivenInvalidGetItemStatementRequest_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->validator = $this->createStub( GetItemStatementValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest(
					'X123',
					'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest(
					'Q999999999',
					'Q999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenStatementIdDoesNotMatchItemId_throws(): void {
		$statementId = 'Q111111111$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				new GetItemStatementRequest( 'Q1', $statementId )
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	private function newUseCase(): GetItemStatement {
		return new GetItemStatement(
			$this->validator,
			$this->assertItemExists,
			$this->getStatement
		);
	}

}
