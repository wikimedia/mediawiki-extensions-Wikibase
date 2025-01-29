<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\FieldsFilterValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\FieldsFilterValidatingDeserializer
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
			$this->assertSame( UseCaseError::INVALID_QUERY_PARAMETER, $useCaseEx->getErrorCode() );
			$this->assertSame(
				"Invalid query parameter: '_fields'",
				$useCaseEx->getErrorMessage()
			);
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => '_fields' ], $useCaseEx->getErrorContext() );
		}
	}

}
