<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsPropertyId(): void {
		$request = $this->createStub( PropertyIdRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		$this->assertEquals(
			new NumericPropertyId( 'P123' ),
			( new PropertyIdRequestValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( PropertyIdRequest::class );
		$invalidId = 'Q123';
		$request->method( 'getPropertyId' )->willReturn( $invalidId );

		try {
			( new PropertyIdRequestValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid property ID: $invalidId", $useCaseEx->getErrorMessage() );
		}
	}

}
