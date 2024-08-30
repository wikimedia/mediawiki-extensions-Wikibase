<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchedStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchedStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedStatementValidatorTest extends TestCase {

	public function testValidateStatement_withValidStatement(): void {
		$patchedStatement = [ 'my' => 'patchedStatement' ];
		$validatedStatement = NewStatement::noValueFor( 'P123' )->build();
		$statementValidator = $this->createMock( StatementValidator::class );
		$statementValidator
			->expects( $this->once() )
			->method( 'validate' )
			->with( $patchedStatement )
			->willReturn( null );
		$statementValidator
			->method( 'getValidatedStatement' )
			->willReturn( $validatedStatement );

		$this->assertSame(
			$validatedStatement,
			( new PatchedStatementValidator( $statementValidator ) )->validateAndDeserializeStatement( $patchedStatement ),
		);
	}

	/**
	 * @dataProvider invalidStatementsTypeProvider
	 *
	 * @param UseCaseError $expectedError
	 * @param mixed $serialization
	 */
	public function testWithInvalidStatementsType( UseCaseError $expectedError, $serialization ): void {
		$statementValidator = $this->createMock( StatementValidator::class );

		try {
			( new PatchedStatementValidator( $statementValidator ) )->validateAndDeserializeStatement( $serialization );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidStatementsTypeProvider(): Generator {
		yield 'invalid serialization - string' => [
			UseCaseError::newPatchResultInvalidValue( '', 'not an array' ),
			'not an array',
		];

		yield 'invalid serialization - sequential array' => [
			UseCaseError::newPatchResultInvalidValue( '', [ 'not', 'an', 'associative', 'array' ] ),
			[ 'not', 'an', 'associative', 'array' ],
		];
	}

	/**
	 * @dataProvider invalidPatchedStatementProvider
	 */
	public function testValidateStatement_withInvalidStatement(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		?array $expectedErrorContext
	): void {
		$patchedStatement = [ 'my' => 'patched statement' ];
		$statementValidator = $this->createMock( StatementValidator::class );
		$statementValidator->method( 'validate' )
			->with( $patchedStatement )
			->willReturn( $validationError );

		try {
			( new PatchedStatementValidator( $statementValidator ) )->validateAndDeserializeStatement( $patchedStatement );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
			$this->assertSame( $expectedErrorContext, $e->getErrorContext() );
		}
	}

	public static function invalidPatchedStatementProvider(): Generator {
		yield 'from invalid patched statement (missing field)' => [
			new ValidationError(
				StatementValidator::CODE_MISSING_FIELD,
				[
					StatementValidator::CONTEXT_PATH => '',
					StatementValidator::CONTEXT_FIELD => 'property',
				]
			),
			UseCaseError::PATCH_RESULT_MISSING_FIELD,
			'Required field missing in patch result',
			[ UseCaseError::CONTEXT_PATH => '', UseCaseError::CONTEXT_FIELD => 'property' ],
		];

		yield 'from invalid patched statement (invalid field)' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD, [
				StatementValidator::CONTEXT_PATH => '/rank',
				StatementValidator::CONTEXT_VALUE => 'not-a-valid-rank',
			] ),
			UseCaseError::PATCH_RESULT_INVALID_VALUE,
			'Invalid value in patch result',
			[
				UseCaseError::CONTEXT_PATH => '/rank',
				UseCaseError::CONTEXT_VALUE => 'not-a-valid-rank',
			],
		];
	}

}
