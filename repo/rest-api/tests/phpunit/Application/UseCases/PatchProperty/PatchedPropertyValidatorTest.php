<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchProperty;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchedPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchedPropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidatorTest extends TestCase {

	private PropertyDeserializer $propertyDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyDeserializer = $this->createStub( PropertyDeserializer::class );
	}

	public function testValid(): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string'
		);

		$propertySerialization = [
			'id' => 'P123',
			'type' => 'property',
			'data-type' => 'string',
			'labels' => [ 'en' => 'english-label' ],
		];

		$expectedProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'english-label' ) ] ),
			),
			'string'
		);
		$this->propertyDeserializer = $this->createStub( PropertyDeserializer::class );
		$this->propertyDeserializer->method( 'deserialize' )->willReturn( $expectedProperty );

		$this->assertEquals(
			$expectedProperty,
			$this->newValidator()->validateAndDeserialize( $propertySerialization, $originalProperty )
		);
	}

	public function testIgnoresPropertyIdRemoval(): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string'
		);

		$patchedProperty = [
			'type' => 'property',
			'data-type' => 'string',
			'labels' => [ 'en' => 'english-label' ],
		];

		$expectedProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'english-label' ) ] ) ),
			'string'
		);

		$this->propertyDeserializer = $this->createStub( PropertyDeserializer::class );
		$this->propertyDeserializer->method( 'deserialize' )->willReturn( $expectedProperty );
		$validatedProperty = $this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty );

		$this->assertEquals( $originalProperty->getId(), $validatedProperty->getId() );
	}

	/**
	 * @dataProvider topLevelValidationProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedProperty, Exception $expectedError ): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string'
		);

		try {
			$this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function topLevelValidationProvider(): Generator {
		yield 'unexpected field' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data-type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
				'foo' => 'bar',
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_UNEXPECTED_FIELD,
				"The patched property contains an unexpected field: 'foo'"
			),
		];

		yield "missing 'data-type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_MISSING_FIELD,
				"Mandatory field missing in the patched property: 'data-type'",
				[ UseCaseError::CONTEXT_PATH => 'data-type' ]
			),
		];

		yield 'invalid field' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data-type' => 'string',
				'labels' => 'illegal string',
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
				"Invalid input for 'labels' in the patched property",
				[ UseCaseError::CONTEXT_PATH => 'labels', UseCaseError::CONTEXT_VALUE => 'illegal string' ]
			),
		];

		yield "Illegal modification 'id' field" => [
			[
				'id' => 'P12',
				'type' => 'property',
				'data-type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_ID,
				'Cannot change the ID of the existing property'
			),
		];

		yield "Illegal modification 'data-type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data-type' => 'wikibase-item',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE,
				'Cannot change the datatype of the existing property'
			),
		];
	}

	private function newValidator(): PatchedPropertyValidator {
		return new PatchedPropertyValidator(
			$this->propertyDeserializer
		);
	}

}
