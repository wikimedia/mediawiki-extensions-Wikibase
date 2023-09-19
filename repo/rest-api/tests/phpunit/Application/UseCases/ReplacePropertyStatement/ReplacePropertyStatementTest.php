<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplacePropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class ReplacePropertyStatementTest extends TestCase {

	private ReplacePropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private ReplaceStatement $replaceStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->replaceStatement = $this->createStub( ReplaceStatement::class );
	}

	public function testGivenValidRequest_returnsReplaceStatementResponse(): void {
		$propertyId = TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY;
		$request = $this->newUseCaseRequest( $propertyId, "$propertyId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE" );
		$expectedResponse = $this->createStub( ReplaceStatementResponse::class );
		$this->replaceStatement = $this->createMock( ReplaceStatement::class );
		$this->replaceStatement->method( 'execute' )
			->with( $request )
			->willReturn( $expectedResponse );

		$this->assertSame( $expectedResponse, $this->newUseCase()->execute( $request ) );
	}

	public function testGivenInvalidReplacePropertyStatementRequest_throwsUseCaseError(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->validator = $this->createStub( ReplacePropertyStatementValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( 'X123', 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			);

			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyDoesNotExist_throwsUseCaseError(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertPropertyExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( 'P999999999', 'P999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			);

			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenStatementIdPrefixDoesNotMatchPropertyId_throwsUseCaseError(): void {
		$statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( 'P321', $statementId ) );

			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	private function newUseCase(): ReplacePropertyStatement {
		return new ReplacePropertyStatement(
			$this->validator,
			$this->assertPropertyExists,
			$this->replaceStatement
		);
	}

	private function newUseCaseRequest( string $propertyId, string $statementId ): ReplacePropertyStatementRequest {
		$useCaseRequest = $this->createStub( ReplacePropertyStatementRequest::class );
		$useCaseRequest->method( 'getPropertyId' )->willReturn( $propertyId );
		$useCaseRequest->method( 'getStatementId' )->willReturn( $statementId );
		$useCaseRequest->method( 'getStatement' )->willReturn( [
			'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
			'value' => [ 'type' => 'novalue' ],
		] );

		return $useCaseRequest;
	}

}
