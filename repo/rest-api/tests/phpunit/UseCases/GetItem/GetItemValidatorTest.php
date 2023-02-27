<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemValidatorTest extends TestCase {

	/**
	 * @dataProvider dataProviderPass
	 * @doesNotPerformAssertions
	 */
	public function testValidatePass( GetItemRequest $request ): void {
		( new GetItemValidator( new ItemIdValidator() ) )->assertValidRequest( $request );
	}

	public function dataProviderPass(): Generator {
		yield 'valid ID with empty fields' => [
			new GetItemRequest( 'Q123' ),
		];

		yield 'valid ID and fields' => [
			new GetItemRequest( 'Q123', [ 'type', 'labels', 'descriptions' ] ),
		];
	}

	/**
	 * @dataProvider dataProviderFail
	 */
	public function testValidateFail(
		GetItemRequest $request,
		string $expectedCode,
		string $expectedMessage
	): void {
		try {
			( new GetItemValidator( new ItemIdValidator() ) )->assertValidRequest( $request );
		} catch ( UseCaseException $e ) {
			$this->assertEquals( $expectedCode, $e->getErrorCode() );
			$this->assertEquals( $expectedMessage, $e->getErrorMessage() );
		}
	}

	public function dataProviderFail(): Generator {
		yield 'invalid item ID' => [
			new GetItemRequest( 'X123' ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123',
		];
		yield 'invalid field' => [
			new GetItemRequest( 'Q123', [ 'type', 'unknown_field' ] ),
			ErrorResponse::INVALID_FIELD,
			'Not a valid field: unknown_field',
		];
		yield 'invalid item ID and invalid field' => [
			new GetItemRequest( 'X123', [ 'type', 'unknown_field' ] ),
			ErrorResponse::INVALID_ITEM_ID,
			'Not a valid item ID: X123',
		];
	}
}
