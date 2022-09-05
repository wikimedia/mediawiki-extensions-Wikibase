<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
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
		string $expectedMessage
	): void {
		$response = PatchItemStatementErrorResponse::newFromValidationError( $validationError );

		$this->assertEquals( $expectedCode, $response->getCode() );
		$this->assertEquals( $expectedMessage, $response->getMessage() );
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
