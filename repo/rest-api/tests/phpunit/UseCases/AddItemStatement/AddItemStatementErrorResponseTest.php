<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\AddItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementErrorResponseTest extends TestCase {

	/**
	 * @dataProvider validationErrorDataProvider
	 */
	public function testNewFromValidationError( ValidationError $validationError, string $expectedCode, string $expectedMessage ): void {
		$response = AddItemStatementErrorResponse::newFromValidationError( $validationError );

		$this->assertEquals( $expectedCode, $response->getCode() );
		$this->assertEquals( $expectedMessage, $response->getMessage() );
	}

	public function validationErrorDataProvider(): \Generator {
		yield 'from invalid item ID' => [
			new ValidationError( ItemIdValidator::CODE_INVALID, [ ItemIdValidator::ERROR_CONTEXT_VALUE => 'X123' ] ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123'
		];

		yield 'from invalid statement data' => [
			new ValidationError( StatementValidator::CODE_INVALID ),
			ErrorResponse::INVALID_STATEMENT_DATA,
			'Invalid statement data provided'
		];
	}

	public function testNewFromUnknownCode(): void {
		$this->expectException( \LogicException::class );

		AddItemStatementErrorResponse::newFromValidationError(
			new ValidationError( 'unknown' )
		);
	}

}
