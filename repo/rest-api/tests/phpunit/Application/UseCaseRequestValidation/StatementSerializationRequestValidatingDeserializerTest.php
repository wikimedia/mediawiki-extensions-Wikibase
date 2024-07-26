<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementSerializationRequestValidatingDeserializerTest extends TestCase {

	private const EXISTING_STRING_PROPERTY_ID = 'P3041';

	public function testGivenValidRequest_returnsStatement(): void {
		$request = $this->createStub( StatementSerializationRequest::class );
		$request->method( 'getStatement' )->willReturn( [
			'property' => [ 'id' => 'P123' ],
			'value' => [ 'type' => 'novalue' ],
		] );
		$expectedStatement = NewStatement::noValueFor( 'P123' )->build();
		$statementValidator = $this->createStub( StatementValidator::class );
		$statementValidator->method( 'getValidatedStatement' )->willReturn( $expectedStatement );

		$this->assertEquals(
			$expectedStatement,
			( new StatementSerializationRequestValidatingDeserializer( $statementValidator ) )->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider provideInvalidStatementSerialization
	 */
	public function testGivenInvalidRequest_throws( array $statementSerialization, UseCaseError $expectedError ): void {
		$request = $this->createStub( StatementSerializationRequest::class );
		$request->method( 'getStatement' )->willReturn( $statementSerialization );

		try {
			$this->newStatementSerializationRVD()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function provideInvalidStatementSerialization(): Generator {
		// TODO: should invalid 'id' be ignored? If so create a test that asserts that.
		// yield "invalid 'id' value" => [
		// 	[ 'id' => 'invalid', 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'novalue' ] ],
		// 	UseCaseError::newInvalidValue( '/statement/id' ),
		// ];

		yield "invalid 'rank' value" => [
			[ 'rank' => 'invalid', 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newInvalidValue( '/statement/rank' ),
		];

		yield "invalid 'property' value - string" => [
			[ 'property' => 'invalid', 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newInvalidValue( '/statement/property' ),
		];

		yield "invalid 'property' value - sequential array" => [
			[ 'property' => [ 'not an associative array' ], 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newInvalidValue( '/statement/property' ),
		];

		yield "invalid 'property/id' value" => [
			[ 'property' => [ 'id' => 123 ], 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newInvalidValue( '/statement/property/id' ),
		];

		// TODO: should invalid 'data-type' be ignored? If so create a test that asserts that.
		// yield "invalid 'property/data-type' value" => [
		// 	[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID, 'data-type' => 'invalid' ], 'value' => [ 'type' => 'novalue' ] ],
		// 	UseCaseError::newInvalidValue( '/statement/property/data-type' ),
		// ];

		yield "invalid 'value' value - string" => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => 'not an array' ],
			UseCaseError::newInvalidValue( '/statement/value' ),
		];

		yield "invalid 'value' value - sequential array " => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'not an associative array' ] ],
			UseCaseError::newInvalidValue( '/statement/value' ),
		];

		yield "invalid 'value/type' value" => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'invalid' ] ],
			UseCaseError::newInvalidValue( '/statement/value/type' ),
		];

		yield "invalid 'value/content' value" => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'value', 'content' => 711 ] ],
			UseCaseError::newInvalidValue( '/statement/value/content' ),
		];

		yield "invalid 'qualifiers' value - string" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => 'not an array',
			],
			UseCaseError::newInvalidValue( '/statement/qualifiers' ),
		];

		yield "invalid 'qualifiers' value - associative array" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => [ 'invalid' => 'qualifiers' ],
			],
			UseCaseError::newInvalidValue( '/statement/qualifiers' ),
		];

		yield "invalid 'qualifiers/0' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => [ 'not a qualifier' ],
			],
			UseCaseError::newInvalidValue( '/statement/qualifiers/0' ),
		];

		yield "invalid 'qualifiers/1/property' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => [
					[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'novalue' ] ],
					[ 'property' => self::EXISTING_STRING_PROPERTY_ID, 'value' => [ 'type' => 'somevalue' ] ],
				],
			],
			UseCaseError::newInvalidValue( '/statement/qualifiers/1/property' ),
		];

		yield "invalid 'qualifiers/0/value' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => [ [ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'somevalue' ] ] ],
			],
			UseCaseError::newInvalidValue( '/statement/qualifiers/0/value' ),
		];

		yield "invalid 'references' value - string " => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => 'not an array',
			],
			UseCaseError::newInvalidValue( '/statement/references' ),
		];

		yield "invalid 'references' value - associative array " => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [ 'invalid' => 'references' ],
			],
			UseCaseError::newInvalidValue( '/statement/references' ),
		];

		yield "invalid 'references/0' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [ 'not an associative array' ],
			],
			UseCaseError::newInvalidValue( '/statement/references/0' ),
		];

		yield "invalid 'references/0/parts' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [ [ 'parts' => 'not an array' ] ],
			],
			UseCaseError::newInvalidValue( '/statement/references/0/parts' ),
		];

		yield "invalid 'references/0/parts/0' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [ [ 'parts' => [ 'not a valid property value pair' ] ] ],
			],
			UseCaseError::newInvalidValue( '/statement/references/0/parts/0' ),
		];

		yield "invalid 'references/0/parts/0/value' value" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [
					[
						'parts' => [
							[
								'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
								'value' => [ 'somevalue' ],
							],
						],
					],
				],
			],
			UseCaseError::newInvalidValue( '/statement/references/0/parts/0/value' ),
		];

		yield "missing 'property' field" => [
			[ 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newMissingField( '/statement', 'property' ),
		];

		yield "missing 'property/id' field" => [
			[ 'property' => [ 'data-type' => 'string' ], 'value' => [ 'type' => 'novalue' ] ],
			UseCaseError::newMissingField( '/statement/property', 'id' ),
		];

		yield "missing 'value' field" => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ] ],
			UseCaseError::newMissingField( '/statement', 'value' ),
		];

		yield "missing 'value/type' field" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'content' => 'some string value' ],
			],
			UseCaseError::newMissingField( '/statement/value', 'type' ),
		];

		yield "missing 'value/content' field" => [
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'value' ] ],
			UseCaseError::newMissingField( '/statement/value', 'content' ),
		];

		yield "missing 'qualifiers/1/property' field" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'qualifiers' => [
					[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ], 'value' => [ 'type' => 'novalue' ] ],
					[ 'value' => [ 'somevalue' ] ],
				],
			],
			UseCaseError::newMissingField( '/statement/qualifiers/1', 'property' ),
		];

		yield "missing 'references/1/parts' field" => [
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'novalue' ],
				'references' => [
					[ 'parts' => [] ],
					[],
				],
			],
			UseCaseError::newMissingField( '/statement/references/1', 'parts' ),
		];
	}

	private function newStatementSerializationRVD(): StatementSerializationRequestValidatingDeserializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_ID ), 'string' );

		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			new BasicEntityIdParser(),
			$dataTypeLookup,
			new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
				WikibaseRepo::getSnakValueDeserializer(),
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);

		return new StatementSerializationRequestValidatingDeserializer(
			new StatementValidator(
				new StatementDeserializer(
					$propertyValuePairDeserializer,
					new ReferenceDeserializer( $propertyValuePairDeserializer )
				)
			)
		);
	}

}
