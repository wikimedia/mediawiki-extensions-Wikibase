<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdFilterValidatingDeserializerTest extends TestCase {

	public function testGivenValidId_returnsPropertyId(): void {
		$this->assertEquals(
			new NumericPropertyId( 'P123' ),
			( new PropertyIdFilterValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( 'P123' )
		);
	}

	public function testGivenInvalidId_throws(): void {
		$invalidId = 'Q123';
		try {
			( new PropertyIdFilterValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( $invalidId );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid property ID: $invalidId", $useCaseEx->getErrorMessage() );
		}
	}

}
