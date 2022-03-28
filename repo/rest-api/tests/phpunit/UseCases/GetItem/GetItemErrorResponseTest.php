<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\ValidationError;

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
			new ValidationError( "X123", GetItemValidator::SOURCE_ITEM_ID ),
			ErrorResponse::INVALID_ITEM_ID,
			"Not a valid item ID: X123"
		];

		yield "from invalid field" => [
			new ValidationError( "unknown_field", GetItemValidator::SOURCE_FIELDS ),
			ErrorResponse::INVALID_FIELD,
			"Not a valid field: unknown_field"
		];
	}

	/**
	 * @dataProvider dataProviderFail
	 */
	public function testValidateFail( GetItemRequest $request, string $expectedSource ): void {
		$error = ( new GetItemValidator() )->validate( $request );

		$this->assertNotNull( $error );
		$this->assertEquals( $expectedSource, $error->getSource() );
	}

	public function dataProviderFail(): \Generator {
		yield "invalid item ID" => [
			new GetItemRequest( "X123" ),
			GetItemValidator::SOURCE_ITEM_ID
		];

		yield "invalid field" => [
			new GetItemRequest( "Q123", [ 'type', 'unknown_field' ] ),
			GetItemValidator::SOURCE_FIELDS
		];

		yield "invalid item ID and invalid field" => [
			new GetItemRequest( "X123", [ 'type', 'unknown_field' ] ),
			GetItemValidator::SOURCE_ITEM_ID
		];
	}
}
