<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidationResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemValidatorTest extends TestCase {

	public function testValidatePass(): void {
		$request = new GetItemRequest( "Q123" );
		$result = ( new GetItemValidator() )->validate( $request );

		$this->assertFalse( $result->hasError() );
	}

	public function testValidateFail(): void {
		$itemId = "X123";
		$request = new GetItemRequest( $itemId );
		$result = ( new GetItemValidator() )->validate( $request );

		$this->assertTrue( $result->hasError() );
		$this->assertEquals( GetItemValidationResult::SOURCE_ITEM_ID, $result->getError()->getSource() );
	}
}
