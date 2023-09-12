<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PatchRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PatchRequestValidatingDeserializer
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
		array $expectedErrorContext
	): void {
		$invalidPatch = [ 'this is' => 'not a valid patch' ];
		$request = $this->createStub( PatchRequest::class );
		$request->method( 'getPatch' )->willReturn( $invalidPatch );

		$jsonPatchValidator = $this->createMock( JsonPatchValidator::class );
		$jsonPatchValidator->method( 'validate' )
			->with( $invalidPatch )
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
			UseCaseError::INVALID_PATCH,
			'The provided patch is invalid',
			[],
		];

		$operation = [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from invalid patch operation' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_OPERATION, [ JsonPatchValidator::CONTEXT_OPERATION => $operation ] ),
			UseCaseError::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			[ UseCaseError::CONTEXT_OPERATION => $operation ],
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
			UseCaseError::INVALID_PATCH_FIELD_TYPE,
			"The value of 'op' must be of type string",
			[
				UseCaseError::CONTEXT_OPERATION => $operation,
				UseCaseError::CONTEXT_FIELD => 'op',
			],
		];

		$operation = [ 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			[
				UseCaseError::CONTEXT_OPERATION => $operation,
				UseCaseError::CONTEXT_FIELD => 'op',
			],
		];
	}

}
