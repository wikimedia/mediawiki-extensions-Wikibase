<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
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
	 */
	public function testValidatePass( GetItemRequest $request ): void {
		$error = ( new GetItemValidator( new ItemIdValidator() ) )->validate( $request );

		$this->assertNull( $error );
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
	public function testValidateFail( GetItemRequest $request, string $expectedCode ): void {
		$result = ( new GetItemValidator( new ItemIdValidator() ) )->validate( $request );

		$this->assertNotNull( $result );
		$this->assertEquals( $expectedCode, $result->getCode() );
	}

	public function dataProviderFail(): Generator {
		yield 'invalid item ID' => [
			new GetItemRequest( 'X123' ),
			ItemIdValidator::CODE_INVALID,
		];

		yield 'invalid field' => [
			new GetItemRequest( 'Q123', [ 'type', 'unknown_field' ] ),
			GetItemValidator::CODE_INVALID_FIELD,
		];

		yield 'invalid item ID and invalid field' => [
			new GetItemRequest( 'X123', [ 'type', 'unknown_field' ] ),
			ItemIdValidator::CODE_INVALID,
		];
	}
}
