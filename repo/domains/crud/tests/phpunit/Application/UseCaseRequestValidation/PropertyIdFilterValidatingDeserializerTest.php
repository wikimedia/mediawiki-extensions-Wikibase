<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterValidatingDeserializer
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
			$this->assertSame( UseCaseError::INVALID_QUERY_PARAMETER, $useCaseEx->getErrorCode() );
			$this->assertSame(
				"Invalid query parameter: 'property'",
				$useCaseEx->getErrorMessage()
			);
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'property' ], $useCaseEx->getErrorContext() );
		}
	}

}
