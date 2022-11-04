<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonDiff\JsonDiff;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatchValidator;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\PatchInvalidFieldTypeValidationError;
use Wikibase\Repo\RestApi\Validation\PatchInvalidOpValidationError;
use Wikibase\Repo\RestApi\Validation\PatchMissingFieldValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidatorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( !class_exists( JsonDiff::class ) ) {
			$this->markTestSkipped( 'Skipping while swaggest/json-diff has not made it to mediawiki/vendor yet (T316245).' );
		}
	}

	public function testInvalidJsonPatch(): void {
		$source = 'test source';
		$error = ( new JsonDiffJsonPatchValidator() )->validate( [ 'invalid JSON Patch' ], $source );

		$this->assertSame( $source, $error->getSource() );
		$this->assertEmpty( $error->getContext() );
	}

	/**
	 * @dataProvider provideInvalidJsonPatch
	 */
	public function testInvalidJsonPatch_specificExceptions( string $errorInstance, array $patch, ?array $context ): void {
		$source = 'test source';
		$error = ( new JsonDiffJsonPatchValidator() )->validate( $patch, $source );

		$this->assertInstanceOf( $errorInstance, $error );
		$this->assertSame( $source, $error->getSource() );
		$this->assertSame( $context, $error->getContext() );
	}

	public function provideInvalidJsonPatch(): Generator {
		$invalidOperationObject = [ 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'missing "op" field' => [
			PatchMissingFieldValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'op' ]
		];

		$invalidOperationObject = [ 'op' => 'add', 'value' => 'foo' ];
		yield 'missing "path" field' => [
			PatchMissingFieldValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'path' ]
		];

		$invalidOperationObject = [ 'op' => 'add', 'path' => '/a/b/c' ];
		yield 'missing "value" field' => [
			PatchMissingFieldValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'value' ]
		];

		$invalidOperationObject = [ 'op' => 'copy', 'path' => '/a/b/c' ];
		yield 'missing "from" field' => [
			PatchMissingFieldValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'from' ]
		];

		$invalidOperationObject = [ 'op' => 'invalid', 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid "op" field' => [
			PatchInvalidOpValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject ]
		];

		$invalidOperationObject = [ 'op' => true, 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid field type - "op" is a boolean not a string' => [
			PatchInvalidFieldTypeValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'op' ]
		];

		$invalidOperationObject = [ 'op' => [ 'foo' => [ 'bar' ], 'baz' => 42 ], 'path' => '/a/b/c', 'value' => 'foo' ];
		yield 'invalid field type - "op" is an object not a string' => [
			PatchInvalidFieldTypeValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'op' ]
		];

		$invalidOperationObject = [ 'op' => 'add', 'path' => [ 'foo', 'bar', 'baz' ], 'value' => 'foo' ];
		yield 'invalid field type - "path" is not a string' => [
			PatchInvalidFieldTypeValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'path' ]
		];

		$invalidOperationObject = [ 'op' => 'move', 'from' => 42, 'path' => '/a/b/c' ];
		yield 'invalid field type - "from" is not a string' => [
			PatchInvalidFieldTypeValidationError::class,
			[ $invalidOperationObject ],
			[ JsonPatchValidator::ERROR_CONTEXT_OPERATION => $invalidOperationObject, JsonPatchValidator::ERROR_CONTEXT_FIELD => 'from' ]
		];
	}

	public function testValidJsonPatch(): void {
		$validPatch = [ [
			'op' => 'replace',
			'path' => '/mainsnak/datavalue/value',
			'value' => 'patched',
		] ];
		$this->assertNull(
			( new JsonDiffJsonPatchValidator() )->validate( $validPatch, 'test source' )
		);
	}

}
