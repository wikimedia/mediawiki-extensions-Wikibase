<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\FieldsFilterValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\FieldsFilterValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FieldsFilterValidatingDeserializerTest extends TestCase {

	private const VALID_FIELDS = [ 'labels', 'descriptions', 'something-else' ];

	public function testGivenValidFields_returnsFields(): void {
		$requestedFields = [ 'labels', 'something-else' ];

		$this->assertEquals(
			$requestedFields,
			( new FieldsFilterValidatingDeserializer( self::VALID_FIELDS ) )->validateAndDeserialize( $requestedFields )
		);
	}

	public function testGivenInvalidFields_throws(): void {
		$invalidField = 'bad-field';
		try {
			( new FieldsFilterValidatingDeserializer( self::VALID_FIELDS ) )
				->validateAndDeserialize( [ 'labels', $invalidField, 'descriptions' ] );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_FIELD, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid field: $invalidField", $useCaseEx->getErrorMessage() );
		}
	}

}
