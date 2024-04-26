<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchProperty;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
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

	/**
	 * @dataProvider patchedPropertyProvider
	 */
	public function testValid( array $patchedPropertySerialization, Property $expectedPatchedProperty ): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string'
		);

		$this->assertEquals(
			$expectedPatchedProperty,
			$this->newValidator()->validateAndDeserialize( $patchedPropertySerialization, $originalProperty )
		);
	}

	public static function patchedPropertyProvider(): Generator {
		yield 'minimal property' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data-type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
			],
			new Property(
				new NumericPropertyId( 'P123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
				),
				'string'
			),
		];
		yield 'property with all fields' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data-type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
				'descriptions' => [ 'en' => 'english-description' ],
				'aliases' => [ 'en' => [ 'english-alias' ] ],
				'statements' => [
					'P321' => [
						[
							'property' => [ 'id' => 'P321' ],
							'value' => [ 'type' => 'somevalue' ],
						],
					],
				],
			],
			new Property(
				new NumericPropertyId( 'P123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
					new TermList( [ new Term( 'en', 'english-description' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'english-alias' ] ) ] )
				),
				'string',
				new StatementList( NewStatement::someValueFor( 'P321' )->build() )
			),
		];
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
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new PatchedPropertyValidator(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer(),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

}
