<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdFilterRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdFilterRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdFilterRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsPropertyId(): void {
		$request = $this->createStub( PropertyIdFilterRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( 'P123' );

		$this->assertEquals(
			new NumericPropertyId( 'P123' ),
			( new PropertyIdFilterRequestValidatingDeserializer( new PropertyIdValidator() ) )
				->validateAndDeserialize( $request )
		);
	}

	public function testGivenRequestWithoutPropertyIdFilter_returnsPropertyId(): void {
		$request = $this->createStub( PropertyIdFilterRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( null );

		$this->assertNull(
			( new PropertyIdFilterRequestValidatingDeserializer( new PropertyIdValidator() ) )
				->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider provideInvalidPropertyId
	 */
	public function testGivenInvalidPropertyIdFilterRequest_throws( string $invalidPropertyId ): void {
		$propertyIdValidator = $this->createStub( PropertyIdValidator::class );
		$propertyIdValidator->method( 'validate' )
			->willReturn( new ValidationError(
				PropertyIdValidator::CODE_INVALID,
				[ PropertyIdValidator::CONTEXT_VALUE => 'X123' ]
			) );

		$request = $this->createStub( PropertyIdFilterRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( $invalidPropertyId );

		try {
			( new PropertyIdFilterRequestValidatingDeserializer( new PropertyIdValidator() ) )
				->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid property ID: $invalidPropertyId", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PROPERTY_ID => $invalidPropertyId ], $e->getErrorContext() );
		}
	}

	public function provideInvalidPropertyId(): Generator {
		yield 'invalid truthy id' => [ 'X123' ];
		yield 'invalid falsy id' => [ '0' ];
	}

}
