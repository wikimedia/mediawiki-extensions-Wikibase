<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemErrorResponseTest extends TestCase {

	/**
	 * @dataProvider validationErrorDataProvider
	 */
	public function testNewFromValidationError( ValidationError $validationError, string $expectedCode, string $expectedMessage ): void {
		$response = GetItemErrorResponse::newFromValidationError( $validationError );

		$this->assertEquals( $expectedCode, $response->getCode() );
		$this->assertEquals( $expectedMessage, $response->getMessage() );
	}

	public function validationErrorDataProvider(): \Generator {
		yield "from invalid item ID" => [
			new ValidationError( GetItemValidator::SOURCE_ITEM_ID, [ ItemIdValidator::ERROR_CONTEXT_VALUE => 'X123' ] ),
			ErrorResponse::INVALID_ITEM_ID,
			"Not a valid item ID: X123"
		];

		yield "from invalid field" => [
			new ValidationError( GetItemValidator::SOURCE_FIELDS, [ GetItemValidator::ERROR_CONTEXT_FIELD_VALUE => 'unknown_field' ] ),
			ErrorResponse::INVALID_FIELD,
			"Not a valid field: unknown_field"
		];
	}

	public function testNewFromUnknownSource(): void {
		$this->expectException( \LogicException::class );

		GetItemErrorResponse::newFromValidationError(
			new ValidationError( 'unknown' )
		);
	}

}
