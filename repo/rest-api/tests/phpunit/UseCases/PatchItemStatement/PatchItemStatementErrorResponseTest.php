<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorResponseTest extends TestCase {

	/**
	 * @dataProvider provideValidationError
	 */
	public function testNewFromValidationError(
		ValidationError $validationError,
		string $expectedCode,
		string $expectedMessage,
		array $expectedContext = null
	): void {
		$response = PatchItemStatementErrorResponse::newFromValidationError( $validationError );

		$this->assertEquals( $expectedCode, $response->getCode() );
		$this->assertEquals( $expectedMessage, $response->getMessage() );
		$this->assertEquals( $expectedContext, $response->getContext() );
	}

	public function provideValidationError(): \Generator {
		yield 'from invalid item ID' => [
			new ValidationError( ItemIdValidator::CODE_INVALID, [ ItemIdValidator::ERROR_CONTEXT_VALUE => 'X123' ] ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123'
		];

		yield 'from invalid statement ID' => [
			new ValidationError(
				StatementIdValidator::CODE_INVALID,
				[ StatementIdValidator::ERROR_CONTEXT_VALUE => 'Q123$INVALID_STATEMENT_ID' ]
			),
			ErrorResponse::INVALID_STATEMENT_ID,
			'Not a valid statement ID: Q123$INVALID_STATEMENT_ID'
		];

		yield 'from invalid patch' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID ),
			ErrorResponse::INVALID_PATCH,
			'The provided patch is invalid'
		];

		$context = [
			JsonPatchValidator::ERROR_CONTEXT_OPERATION => [ 'path' => '/a/b/c', 'value' => 'test' ],
			JsonPatchValidator::ERROR_CONTEXT_FIELD => 'op',
		];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, $context ),
			ErrorResponse::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			$context
		];

		$context = [ JsonPatchValidator::ERROR_CONTEXT_OPERATION => [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ] ];
		yield 'from invalid patch operation' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_OPERATION, $context ),
			ErrorResponse::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			$context
		];

		$context = [
			JsonPatchValidator::ERROR_CONTEXT_OPERATION => [
				'op' => [ 'not', [ 'a' => 'string' ] ],
				'path' => '/a/b/c', 'value' => 'test',
			],
			JsonPatchValidator::ERROR_CONTEXT_FIELD => 'op',
		];
		yield 'from invalid patch field type' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_FIELD_TYPE, $context ),
			ErrorResponse::INVALID_PATCH_FIELD_TYPE,
			"The value of 'op' must be of type string",
			$context
		];

		yield 'from comment too long' => [
			new ValidationError(
				EditMetadataValidator::CODE_COMMENT_TOO_LONG,
				[ EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH => '500' ]
			),
			ErrorResponse::COMMENT_TOO_LONG,
			'Comment must not be longer than 500 characters.'
		];

		yield 'from invalid tag' => [
			new ValidationError(
				EditMetadataValidator::CODE_INVALID_TAG,
				[ EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE => 'bad tag' ]
			),
			ErrorResponse::INVALID_EDIT_TAG,
			'Invalid MediaWiki tag: bad tag'
		];

		yield 'from invalid patched statement (invalid field)' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD ),
			ErrorResponse::PATCHED_STATEMENT_INVALID,
			'The patch results in an invalid statement which cannot be stored'
		];

		yield 'from invalid patched statement (missing field)' => [
			new ValidationError( StatementValidator::CODE_MISSING_FIELD ),
			ErrorResponse::PATCHED_STATEMENT_INVALID,
			'The patch results in an invalid statement which cannot be stored'
		];

		yield 'from invalid patched statement (unknown reason)' => [
			new ValidationError( StatementValidator::CODE_INVALID ),
			ErrorResponse::PATCHED_STATEMENT_INVALID,
			'The patch results in an invalid statement which cannot be stored'
		];
	}

	public function testNewFromUnknownCode(): void {
		$this->expectException( \LogicException::class );

		PatchItemStatementErrorResponse::newFromValidationError(
			new ValidationError( 'unknown' )
		);
	}

}
