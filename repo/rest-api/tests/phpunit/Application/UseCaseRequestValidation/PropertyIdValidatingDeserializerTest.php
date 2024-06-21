<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdValidatingDeserializerTest extends TestCase {

	public function testGivenValidId_returnsPropertyId(): void {
		$this->assertEquals(
			new NumericPropertyId( 'P123' ),
			( new PropertyIdValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( 'P123' )
		);
	}

	public function testGivenInvalidId_throws(): void {
		try {
			( new PropertyIdValidatingDeserializer( new PropertyIdValidator() ) )->validateAndDeserialize( 'Q123' );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $useCaseEx->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'property_id'", $useCaseEx->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'property_id' ], $useCaseEx->getErrorContext() );
		}
	}

}
