<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementErrorResponse
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementErrorResponseTest extends TestCase {

	/**
	 * @dataProvider validationErrorDataProvider
	 */
	public function testNewFromValidationError(
		ValidationError $validationError,
		string $expectedCode,
		string $expectedMessage,
		?array $expectedContext
	): void {
		$response = ReplaceItemStatementErrorResponse::newFromValidationError( $validationError );

		$this->assertSame( $expectedCode, $response->getCode() );
		$this->assertSame( $expectedMessage, $response->getMessage() );
		$this->assertSame( $expectedContext, $response->getContext() );
	}

	public function validationErrorDataProvider(): \Generator {
		yield 'from invalid item ID' => [
			new ValidationError( ItemIdValidator::CODE_INVALID, [ ItemIdValidator::ERROR_CONTEXT_VALUE => 'X123' ] ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123',
			null
		];

		yield 'from invalid statement data' => [
			new ValidationError( StatementValidator::CODE_INVALID ),
			ErrorResponse::INVALID_STATEMENT_DATA,
			'Invalid statement data provided',
			null
		];

		$context = [
			StatementValidator::CONTEXT_FIELD_NAME => 'some-field',
			StatementValidator::CONTEXT_FIELD_VALUE => 'foo'
		];
		yield 'from invalid statement field' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD, $context ),
			ErrorResponse::STATEMENT_DATA_INVALID_FIELD,
			'Invalid input for some-field',
			[
				'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
				'value' => $context[StatementValidator::CONTEXT_FIELD_VALUE]
			]
		];

		$context = [ StatementValidator::CONTEXT_FIELD_NAME => 'some-field' ];
		yield 'from missing statement field' => [
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, $context ),
			ErrorResponse::STATEMENT_DATA_MISSING_FIELD,
			'Mandatory field missing in the statement data: some-field',
			[ 'path' => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
		];

		yield 'from edit metadata comment too long' => [
			new ValidationError(
				EditMetadataValidator::CODE_COMMENT_TOO_LONG,
				[ EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH => 'a million' ]
			),
			ErrorResponse::COMMENT_TOO_LONG,
			'Comment must not be longer than a million characters.',
			null
		];

		yield 'from invalid edit tag' => [
			new ValidationError(
				EditMetadataValidator::CODE_INVALID_TAG,
				[ EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE => 'not-a-valid-tag' ]
			),
			ErrorResponse::INVALID_EDIT_TAG,
			'Invalid MediaWiki tag: not-a-valid-tag',
			null
		];
	}

	public function testNewFromUnknownCode(): void {
		$this->expectException( \LogicException::class );

		ReplaceItemStatementErrorResponse::newFromValidationError(
			new ValidationError( 'unknown' )
		);
	}

}
