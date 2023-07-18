<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchedStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchedStatementValidator
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
				[ StatementValidator::CONTEXT_FIELD_NAME => 'property' ]
			),
			UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
			'Mandatory field missing in the patched statement: property',
			[ PatchedStatementValidator::CONTEXT_PATH => 'property' ],
		];

		yield 'from invalid patched statement (invalid field)' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD, [
				StatementValidator::CONTEXT_FIELD_NAME => 'rank',
				StatementValidator::CONTEXT_FIELD_VALUE => 'not-a-valid-rank',
			] ),
			UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
			"Invalid input for 'rank' in the patched statement",
			[
				PatchedStatementValidator::CONTEXT_PATH => 'rank',
				PatchedStatementValidator::CONTEXT_VALUE => 'not-a-valid-rank',
			],
		];
	}
}
