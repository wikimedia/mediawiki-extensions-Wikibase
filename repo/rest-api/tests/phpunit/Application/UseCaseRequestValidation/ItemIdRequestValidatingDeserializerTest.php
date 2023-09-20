<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemIdRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsItemId(): void {
		$request = $this->createStub( ItemIdRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		$this->assertEquals(
			new ItemId( 'Q123' ),
			( new ItemIdRequestValidatingDeserializer( new ItemIdValidator() ) )->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( ItemIdRequest::class );
		$invalidId = 'P123';
		$request->method( 'getItemId' )->willReturn( $invalidId );

		try {
			( new ItemIdRequestValidatingDeserializer( new ItemIdValidator() ) )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidId", $useCaseEx->getErrorMessage() );
		}
	}

}
