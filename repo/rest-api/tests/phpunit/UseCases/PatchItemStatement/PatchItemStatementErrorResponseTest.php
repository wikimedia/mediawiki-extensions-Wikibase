<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\PatchInvalidFieldTypeValidationError;
use Wikibase\Repo\RestApi\Validation\PatchInvalidOpValidationError;
use Wikibase\Repo\RestApi\Validation\PatchMissingFieldValidationError;
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
			new ValidationError( 'X123', PatchItemStatementValidator::SOURCE_ITEM_ID ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123'
		];

		yield 'from invalid statement ID' => [
			new ValidationError( 'Q123$INVALID_STATEMENT_ID', PatchItemStatementValidator::SOURCE_STATEMENT_ID ),
			ErrorResponse::INVALID_STATEMENT_ID,
			'Not a valid statement ID: Q123$INVALID_STATEMENT_ID'
		];

		yield 'from invalid patch' => [
			new ValidationError( '', PatchItemStatementValidator::SOURCE_PATCH ),
			ErrorResponse::INVALID_PATCH,
			'The provided patch is invalid'
		];

		$context = [ 'operation' => [ 'path' => '/a/b/c', 'value' => 'test' ] ];
		yield 'from missing patch field' => [
			new PatchMissingFieldValidationError( 'op', PatchItemStatementValidator::SOURCE_PATCH, $context ),
			ErrorResponse::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			$context
		];

		$context = [ 'operation' => [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ] ];
		yield 'from invalid patch operation' => [
			new PatchInvalidOpValidationError( 'bad', PatchItemStatementValidator::SOURCE_PATCH, $context ),
			ErrorResponse::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			$context
		];

		$context = [ 'operation' => [ 'op' => [ 'not', [ 'a' => 'string' ] ], 'path' => '/a/b/c', 'value' => 'test' ] ];
		yield 'from invalid patch field type' => [
			new PatchInvalidFieldTypeValidationError( 'op', PatchItemStatementValidator::SOURCE_PATCH, $context ),
			ErrorResponse::INVALID_PATCH_FIELD_TYPE,
			"The value of 'op' must be of type string",
			$context
		];

		yield 'from comment too long' => [
			new ValidationError( '500', PatchItemStatementValidator::SOURCE_COMMENT ),
			ErrorResponse::COMMENT_TOO_LONG,
			'Comment must not be longer than 500 characters.'
		];

		yield 'from invalid tag' => [
			new ValidationError( 'bad tag', PatchItemStatementValidator::SOURCE_EDIT_TAGS ),
			ErrorResponse::INVALID_EDIT_TAG,
			'Invalid MediaWiki tag: bad tag'
		];
	}

	public function testNewFromUnknownSource(): void {
		$this->expectException( \LogicException::class );

		PatchItemStatementErrorResponse::newFromValidationError(
			new ValidationError( 'X123', 'unknown' )
		);
	}

}
