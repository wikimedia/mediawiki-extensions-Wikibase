<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementSerializationRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsStatement(): void {
		$request = $this->createStub( StatementSerializationRequest::class );
		$request->method( 'getStatement' )->willReturn( [
			'property' => [ 'id' => 'P123' ],
			'value' => [ 'type' => 'novalue' ],
		] );
		$expectedStatement = NewStatement::noValueFor( 'P123' )->build();
		$statementValidator = $this->createStub( StatementValidator::class );
		$statementValidator->method( 'getValidatedStatement' )->willReturn( $expectedStatement );

		$this->assertEquals(
			$expectedStatement,
			( new StatementSerializationRequestValidatingDeserializer( $statementValidator ) )->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider statementValidationErrorProvider
	 */
	public function testGivenInvalidRequest_throws( ValidationError $validationError, UseCaseError $expectedError ): void {
		$statementSerialization = [ 'statement serialization stub' ];
		$request = $this->createStub( StatementSerializationRequest::class );
		$request->method( 'getStatement' )->willReturn( $statementSerialization );

		$statementValidator = $this->createMock( StatementValidator::class );
		$statementValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $statementSerialization )
			->willReturn( $validationError );

		try {
			( new StatementSerializationRequestValidatingDeserializer( $statementValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertEquals( $expectedError, $useCaseEx );
		}
	}

	public function statementValidationErrorProvider(): Generator {
		yield 'missing field' => [
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [ StatementValidator::CONTEXT_FIELD_NAME => 'some-field' ] ),
			new UseCaseError(
				UseCaseError::STATEMENT_DATA_MISSING_FIELD,
				'Mandatory field missing in the statement data: some-field',
				[ UseCaseError::CONTEXT_PATH => 'some-field' ]
			),
		];
		yield 'invalid field' => [
			new ValidationError(
				StatementValidator::CODE_INVALID_FIELD,
				[
					StatementValidator::CONTEXT_FIELD_NAME => 'some-field',
					StatementValidator::CONTEXT_FIELD_VALUE => 'some-value',
				]
			),
			new UseCaseError(
				UseCaseError::STATEMENT_DATA_INVALID_FIELD,
				"Invalid input for 'some-field'",
				[
					UseCaseError::CONTEXT_PATH => 'some-field',
					UseCaseError::CONTEXT_VALUE => 'some-value',
				]
			),
		];
	}

}
