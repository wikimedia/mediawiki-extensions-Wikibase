<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestFieldDeserializerFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementTest extends TestCase {

	private GetPropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private GetStatement $getStatement;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->getStatement = $this->createStub( GetStatement::class );
	}

	public function testGivenValidRequest_callsGetStatementUseCase(): void {
		$expectedResponse = $this->createStub( GetStatementResponse::class );
		$this->getStatement = $this->createStub( GetStatement::class );
		$this->getStatement->method( 'execute' )->willReturn( $expectedResponse );

		$this->assertSame(
			$expectedResponse,
			$this->newUseCase()->execute( new GetPropertyStatementRequest(
				'P123',
				'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
			) )
		);
	}

	public function testGivenInvalidGetPropertyStatementRequest_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->validator = $this->createStub( GetPropertyStatementValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetPropertyStatementRequest(
					'X123',
					'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyDoesNotExist_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertPropertyExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetPropertyStatementRequest(
					'P999999999',
					'P999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testStatementSubjectIdNotMatchingPropertyId_throwsUseCaseError(): void {
		$propertyId = 'P111';
		$statementId = 'P1' . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		try {
			$this->newUseCase()->execute( new GetPropertyStatementRequest( $propertyId, $statementId ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	private function newUseCase(): GetPropertyStatement {
		return new GetPropertyStatement(
			$this->validator,
			$this->assertPropertyExists,
			$this->getStatement
		);
	}

}
