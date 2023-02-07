<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidatorTest extends TestCase {

	public function testInvalidJsonPatch(): void {
		$error = ( new JsonDiffJsonPatchValidator() )->validate( [ 'invalid JSON Patch' ] );

		$this->assertSame( JsonPatchValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( [], $error->getContext() );
	}

	/**
	 * @dataProvider provideInvalidJsonPatch
	 */
	public function testInvalidJsonPatch_specificExceptions( string $errorCode, array $patch, ?array $context ): void {
		$error = ( new JsonDiffJsonPatchValidator() )->validate( $patch );

		$this->assertSame( $errorCode, $error->getCode() );
		$this->assertSame( $context, $error->getContext() );
	}

	public function provideInvalidJsonPatch(): Generator {
		$invalidOperationObject = [ 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'missing "op" field' => [
			JsonPatchValidator::CODE_MISSING_FIELD,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'op' ],
		];

		$invalidOperationObject = [ 'op' => 'add', 'value' => 'foo' ];
		yield 'missing "path" field' => [
			JsonPatchValidator::CODE_MISSING_FIELD,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'path' ],
		];

		$invalidOperationObject = [ 'op' => 'add', 'path' => '/a/b/c' ];
		yield 'missing "value" field' => [
			JsonPatchValidator::CODE_MISSING_FIELD,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'value' ],
		];

		$invalidOperationObject = [ 'op' => 'copy', 'path' => '/a/b/c' ];
		yield 'missing "from" field' => [
			JsonPatchValidator::CODE_MISSING_FIELD,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'from' ],
		];

		$invalidOperationObject = [ 'op' => 'invalid', 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid "op" field' => [
			JsonPatchValidator::CODE_INVALID_OPERATION,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject ],
		];

		$invalidOperationObject = [ 'op' => true, 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid field type - "op" is a boolean not a string' => [
			JsonPatchValidator::CODE_INVALID_FIELD_TYPE,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'op' ],
		];

		$invalidOperationObject = [ 'op' => [ 'foo' => [ 'bar' ], 'baz' => 42 ], 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid field type - "op" is an object not a string' => [
			JsonPatchValidator::CODE_INVALID_FIELD_TYPE,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'op' ],
		];

		$invalidOperationObject = [ 'op' => 'add', 'path' => [ 'foo', 'bar', 'baz' ], 'value' => 'foo' ];
		yield 'invalid field type - "path" is not a string' => [
			JsonPatchValidator::CODE_INVALID_FIELD_TYPE,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'path' ],
		];

		$invalidOperationObject = [ 'op' => 'move', 'from' => 42, 'path' => '/a/b/c' ];
		yield 'invalid field type - "from" is not a string' => [
			JsonPatchValidator::CODE_INVALID_FIELD_TYPE,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::CONTEXT_FIELD => 'from' ],
		];
	}

	public function testValidJsonPatch(): void {
		$validPatch = [
			[
				'op' => 'replace',
				'path' => '/value/content',
				'value' => 'patched',
			],
		];
		$this->assertNull(
			( new JsonDiffJsonPatchValidator() )->validate( $validPatch )
		);
	}

}
