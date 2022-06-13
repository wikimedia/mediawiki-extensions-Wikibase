<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\AddItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
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
		yield "from invalid item ID" => [
			new ValidationError( "X123", AddItemStatementValidator::SOURCE_ITEM_ID ),
			ErrorResponse::INVALID_ITEM_ID,
			"Not a valid item ID: X123"
		];

		yield "from invalid statement data" => [
			new ValidationError( json_encode( [ "invalid" => "statement" ] ), AddItemStatementValidator::SOURCE_STATEMENT ),
			ErrorResponse::INVALID_STATEMENT_DATA,
			"Invalid statement data provided"
		];
	}

	public function testNewFromUnknownSource(): void {
		$this->expectException( \LogicException::class );

		AddItemStatementErrorResponse::newFromValidationError(
			new ValidationError( "X123", 'unknown' )
		);
	}

}
