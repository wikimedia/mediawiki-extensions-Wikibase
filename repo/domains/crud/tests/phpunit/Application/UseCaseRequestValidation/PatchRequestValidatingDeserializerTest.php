<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatchValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsPatch(): void {
		$patch = [ [ 'op' => 'test', 'path' => '/some/path', 'value' => 'abc' ] ];
		$request = $this->createStub( PatchRequest::class );
		$request->method( 'getPatch' )->willReturn( $patch );

		$this->assertEquals(
			$patch,
			( new PatchRequestValidatingDeserializer( new JsonDiffJsonPatchValidator() ) )
				->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testAssertValidRequest_withInvalidPatch(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedErrorContext,
		array $invalidPatchDocument = [ [ 'this is' => 'not a valid patch operation' ] ]
	): void {
		$request = $this->createStub( PatchRequest::class );
		$request->method( 'getPatch' )->willReturn( $invalidPatchDocument );

		$jsonPatchValidator = $this->createMock( JsonPatchValidator::class );
		$jsonPatchValidator->method( 'validate' )
			->with( $invalidPatchDocument )
			->willReturn( $validationError );

		try {
			( new PatchRequestValidatingDeserializer( $jsonPatchValidator ) )
				->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
			$this->assertSame( $expectedErrorContext, $e->getErrorContext() );
		}
	}

	public static function invalidPatchProvider(): Generator {
		yield 'from invalid patch' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID ),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/patch'",
			[ UseCaseError::CONTEXT_PATH => '/patch' ],
		];

		$operation = [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from invalid patch operation' => [
			new ValidationError(
				JsonPatchValidator::CODE_INVALID_OPERATION,
				[ JsonPatchValidator::CONTEXT_OPERATION => $operation ]
			),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/patch/0/op'",
			[ UseCaseError::CONTEXT_PATH => '/patch/0/op' ],
			[ $operation ],
		];

		$operation = [
			'op' => [ 'not', [ 'a' => 'string' ] ],
			'path' => '/a/b/c',
			'value' => 'test',
		];
		yield 'from invalid patch field type' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_FIELD_TYPE, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/patch/0/op'",
			[ UseCaseError::CONTEXT_PATH => '/patch/0/op' ],
			[ $operation ],
		];

		$operation = [ 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::MISSING_FIELD,
			'Required field missing',
			[
				UseCaseError::CONTEXT_PATH => '/patch/0',
				UseCaseError::CONTEXT_FIELD => 'op',
			],
			[ $operation ],
		];
	}

}
