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
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchedPropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidatorTest extends TestCase {

	private const LIMIT = 40;

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
			$this->newValidator( $this->createStub( AliasesInLanguageValidator::class ) )
				->validateAndDeserialize( $patchedPropertySerialization, $originalProperty )
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

		$validatedProperty = $this->newValidator( $this->createStub( AliasesInLanguageValidator::class ) )
			->validateAndDeserialize( $patchedProperty, $originalProperty );

		$this->assertEquals( $originalProperty->getId(), $validatedProperty->getId() );
	}

	/**
	 * @dataProvider topLevelValidationErrorProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedProperty, Exception $expectedError ): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string'
		);

		try {
			$this->newValidator( $this->createStub( AliasesInLanguageValidator::class ) )
				->validateAndDeserialize( $patchedProperty, $originalProperty );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function topLevelValidationErrorProvider(): Generator {
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

	/**
	 * @dataProvider aliasesValidationErrorProvider
	 */
	public function testAliasesValidation(
		AliasesInLanguageValidator $aliasesInLanguageValidator,
		array $patchedAliases,
		Exception $expectedError
	): void {
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
			'aliases' => $patchedAliases,
		];

		try {
			$this->newValidator( $aliasesInLanguageValidator )->validateAndDeserialize( $propertySerialization, $originalProperty );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function aliasesValidationErrorProvider(): Generator {
		yield 'empty alias' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'de' => [ '' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_EMPTY,
				"Changed alias for 'de' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => 'de' ]
			),
		];

		$duplicate = 'tomato';
		yield 'duplicate alias' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => [ $duplicate, $duplicate ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_DUPLICATE,
				"Aliases in language 'en' contain duplicate alias: '{$duplicate}'",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => $duplicate ]
			),
		];

		$tooLongAlias = str_repeat( 'A', self::LIMIT + 1 );
		$expectedResponse = new ValidationError( AliasesInLanguageValidator::CODE_TOO_LONG, [
			AliasesInLanguageValidator::CONTEXT_VALUE => $tooLongAlias,
			AliasesInLanguageValidator::CONTEXT_LANGUAGE => 'en',
			AliasesInLanguageValidator::CONTEXT_LIMIT => self::LIMIT,
		] );
		$aliasesInLanguageValidator = $this->createMock( AliasesInLanguageValidator::class );
		$aliasesInLanguageValidator->method( 'validate' )
			->with( new AliasGroup( 'en', [ $tooLongAlias ] ) )
			->willReturn( $expectedResponse );
		yield 'alias too long' => [
			$aliasesInLanguageValidator,
			[ 'en' => [ $tooLongAlias ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_TOO_LONG,
				"Changed alias for 'en' must not be more than " . self::LIMIT . ' characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $tooLongAlias,
					UseCaseError::CONTEXT_CHARACTER_LIMIT => self::LIMIT,
				]
			),
		];

		$invalidAlias = "tab\t tab\t tab";
		$expectedResponse = new ValidationError( AliasesInLanguageValidator::CODE_INVALID, [
			AliasesInLanguageValidator::CONTEXT_VALUE => $invalidAlias,
			AliasesInLanguageValidator::CONTEXT_LANGUAGE => 'en',
			AliasesInLanguageValidator::CONTEXT_PATH => 'en/1',
		] );
		$aliasesInLanguageValidator = $this->createMock( AliasesInLanguageValidator::class );
		$aliasesInLanguageValidator->method( 'validate' )
			->with( new AliasGroup( 'en', [ 'valid alias', $invalidAlias ] ) )
			->willReturn( $expectedResponse );
		yield 'alias contains invalid character' => [
			$aliasesInLanguageValidator,
			[ 'en' => [ 'valid alias', $invalidAlias ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for 'en' is invalid",
				[
					UseCaseError::CONTEXT_PATH => 'en/1',
					UseCaseError::CONTEXT_VALUE => $invalidAlias,
				]
			),
		];

		yield 'aliases in language is not a list' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => [ 'associative array' => 'not a list' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for 'en' is invalid",
				[
					UseCaseError::CONTEXT_PATH => 'en',
					UseCaseError::CONTEXT_VALUE => [ 'associative array' => 'not a list' ],
				]
			),
		];

		yield 'aliases is not an associative array' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'sequential array, not an associative array' ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for 'aliases' is invalid",
				[
					UseCaseError::CONTEXT_PATH => '',
					UseCaseError::CONTEXT_VALUE => [ 'sequential array, not an associative array' ],
				]
			),
		];

		$invalidLanguage = 'not-a-valid-language-code';
		yield 'invalid language code' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ $invalidLanguage => [ 'alias' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_LANGUAGE_CODE,
				"Not a valid language code '{$invalidLanguage}' in changed aliases",
				[ UseCaseError::CONTEXT_LANGUAGE => $invalidLanguage ]
			),
		];
	}

	private function newValidator(
		AliasesInLanguageValidator $aliasesInLanguageValidator
	): PatchedPropertyValidator {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new PatchedPropertyValidator(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesValidator(
				$aliasesInLanguageValidator,
				new LanguageCodeValidator( [ 'ar', 'de', 'en', 'fr' ] ),
				new AliasesDeserializer(),
			),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			),
		);
	}

}
