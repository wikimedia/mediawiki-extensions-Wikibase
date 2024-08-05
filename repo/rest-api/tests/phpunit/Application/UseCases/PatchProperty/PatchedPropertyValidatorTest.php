<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchProperty;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
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
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchedPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Tests\RestApi\Helpers\TestPropertyValuePairDeserializerFactory;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchedPropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidatorTest extends TestCase {

	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private PropertyLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private PropertyDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesInLanguageValidator $aliasesInLanguageValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsSyntaxValidator = new LabelsSyntaxValidator(
			new LabelsDeserializer(),
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en' ] ) )
		);
		$this->descriptionsSyntaxValidator = new DescriptionsSyntaxValidator(
			new DescriptionsDeserializer(),
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en' ] ) )
		);
		$this->labelsContentsValidator = new PropertyLabelsContentsValidator(
			$this->createStub( PropertyLabelValidator::class )
		);
		$this->descriptionsContentsValidator = new PropertyDescriptionsContentsValidator(
			$this->createStub( PropertyDescriptionValidator::class )
		);
		$this->aliasesInLanguageValidator = $this->createStub( AliasesInLanguageValidator::class );
	}

	private const LIMIT = 40;
	private const EXISTING_STATEMENT_ID = 'P123$5FF2B0D8-BEC1-4D30-B88E-347E08AFD659';
	private const EXISTING_STRING_PROPERTY_IDS = [ 'P3685', 'P3177', 'P6920', 'P4877' ];

	/**
	 * @dataProvider patchedPropertyProvider
	 */
	public function testValid( array $patchedPropertySerialization, Property $expectedPatchedProperty ): void {
		$originalProperty = new Property( new NumericPropertyId( 'P123' ), new Fingerprint(), 'string' );

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
				'data_type' => 'string',
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
				'data_type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
				'descriptions' => [ 'en' => 'english-description' ],
				'aliases' => [ 'en' => [ 'english-alias' ] ],
				'statements' => [
					self::EXISTING_STRING_PROPERTY_IDS[0] => [
						[
							'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
							'value' => [ 'type' => 'somevalue' ],
						],
						[
							'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
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
				new StatementList(
					NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[0] )->build(),
					NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[0] )->build()
				)
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
			'data_type' => 'string',
			'labels' => [ 'en' => 'english-label' ],
		];

		$validatedProperty = $this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty );

		$this->assertEquals( $originalProperty->getId(), $validatedProperty->getId() );
	}

	public function testValidateOnlyModifiedLabels(): void {
		$propertyId = new NumericPropertyId( 'P13' );

		$originalProperty = new Property(
			$propertyId,
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'spud' ),
					new Term( 'de', 'Kartoffel' ),
				] ),
				new TermList(),
				null
			),
			'string'
		);

		$patchedProperty = [
			'id' => "$propertyId",
			'type' => 'property',
			'data_type' => 'string',
			'labels' => [ 'en' => 'potato', 'de' => 'Kartoffel', 'ar' => 'بطاطا' ], // only 'en' and 'ar' labels have been patched
		];

		$inputTerms = new PartiallyValidatedLabels( [
			new Term( 'en', 'potato' ),
			new Term( 'de', 'Kartoffel' ),
			new Term( 'ar', 'بطاطا' ),
		] );
		$termsToCompareWith = new PartiallyValidatedDescriptions();

		// expect validation only for the modified labels
		$this->labelsContentsValidator = $this->createMock( PropertyLabelsContentsValidator::class );

		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $inputTerms, $termsToCompareWith, [ 'en', 'ar' ] )
			->willReturn( null );

		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'getValidatedLabels' )
			->willReturn( $inputTerms->asPlainTermList() );

		$this->assertEquals(
			new Property(
				$propertyId,
				new Fingerprint( $inputTerms->asPlainTermList(), $termsToCompareWith->asPlainTermList(), null ),
				'string'
			),
			$this->newValidator( $this->createStub( AliasesInLanguageValidator::class ) )
				->validateAndDeserialize( $patchedProperty, $originalProperty )
		);
	}

	public function testValidateOnlyModifiedDescriptions(): void {
		$propertyId = new NumericPropertyId( 'P13' );

		$originalProperty = new Property(
			$propertyId,
			new Fingerprint(
				new TermList(),
				new TermList( [
					new Term( 'en', 'en-description' ),
					new Term( 'de', 'de-description' ),
				] ),
				null
			),
			'string'
		);

		$patchedProperty = [
			'id' => "$propertyId",
			'type' => 'property',
			'data_type' => 'string',
			'descriptions' => [ 'en' => 'updated-en-description', 'de' => 'de-description', 'ar' => 'ar-description' ],
		];

		$inputTerms = new PartiallyValidatedDescriptions( [
			new Term( 'en', 'updated-en-description' ),
			new Term( 'de', 'de-description' ),
			new Term( 'ar', 'ar-description' ),
		] );
		$termsToCompareWith = new PartiallyValidatedLabels();

		// expect validation only for the modified descriptions
		$this->descriptionsContentsValidator = $this->createMock( PropertyDescriptionsContentsValidator::class );

		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $inputTerms, $termsToCompareWith, [ 'en', 'ar' ] )
			->willReturn( null );

		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'getValidatedDescriptions' )
			->willReturn( $inputTerms->asPlainTermList() );

		$this->assertEquals(
			new Property(
				$propertyId,
				new Fingerprint( $termsToCompareWith->asPlainTermList(), $inputTerms->asPlainTermList(), null ),
				'string'
			),
			$this->newValidator( $this->createStub( AliasesInLanguageValidator::class ) )
				->validateAndDeserialize( $patchedProperty, $originalProperty )
		);
	}

	/**
	 * @dataProvider topLevelValidationErrorProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedProperty, Exception $expectedError ): void {
		$originalProperty = new Property( new NumericPropertyId( 'P123' ), new Fingerprint(), 'string' );

		try {
			$this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty );
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
				'data_type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
				'foo' => 'bar',
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_UNEXPECTED_FIELD,
				"The patched property contains an unexpected field: 'foo'"
			),
		];

		yield "missing 'data_type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_MISSING_FIELD,
				"Mandatory field missing in the patched property: 'data_type'",
				[ UseCaseError::CONTEXT_PATH => 'data_type' ]
			),
		];

		yield 'invalid field' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data_type' => 'string',
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
				'data_type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_ID,
				'Cannot change the ID of the existing property'
			),
		];

		yield "Illegal modification 'data_type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data_type' => 'wikibase-item',
				'labels' => [ 'en' => 'english-label' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE,
				'Cannot change the datatype of the existing property'
			),
		];
	}

	/**
	 * @dataProvider labelsValidationErrorProvider
	 * @dataProvider descriptionsValidationErrorProvider
	 */
	public function testGivenValidationErrorInField_throws(
		callable $getFieldValidator,
		ValidationError $validationError,
		UseCaseError $expectedError,
		array $patchedSerialization = []
	): void {
		$getFieldValidator( $this )->expects( $this->once() )->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->validateAndDeserialize(
				array_merge( [ 'id' => 'P123', 'data_type' => 'string' ], $patchedSerialization ),
				new Property(
					new NumericPropertyId( 'P123' ),
					new Fingerprint(),
					'string'
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function labelsValidationErrorProvider(): Generator {
		$mockSyntaxValidator = function ( self $test ) {
			$test->labelsSyntaxValidator = $this->createMock( LabelsSyntaxValidator::class );
			return $test->labelsSyntaxValidator;
		};
		$mockContentsValidator = function ( self $test ) {
			$test->labelsContentsValidator = $this->createMock( PropertyLabelsContentsValidator::class );
			return $test->labelsContentsValidator;
		};

		$invalidLabels = [ 'not an associative array' ];
		yield 'invalid labels' => [
			$mockSyntaxValidator,
			new ValidationError( LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
				"Invalid input for 'labels' in the patched property",
				[
					UseCaseError::CONTEXT_PATH => 'labels',
					UseCaseError::CONTEXT_VALUE => $invalidLabels,
				]
			),
			[ 'labels' => $invalidLabels ],
		];

		yield 'empty label' => [
			$mockContentsValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_LABEL_EMPTY,
				"Changed label for 'en' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		$longLabel = str_repeat( 'a', 51 );
		$maxLength = 50;
		yield 'label too long' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_TOO_LONG,
				[
					PropertyLabelValidator::CONTEXT_LABEL => $longLabel,
					PropertyLabelValidator::CONTEXT_LANGUAGE => 'en',
					PropertyLabelValidator::CONTEXT_LIMIT => $maxLength,
				]
			),
			UseCaseError::newValueTooLong( '/labels/en', $maxLength, true ),
		];

		$labelOfInvalidType = [ 'invalid', 'label', 'type' ];
		yield 'invalid label type' => [
			$mockSyntaxValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_LABEL => $labelOfInvalidType,
					LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID,
				"Changed label for 'en' is invalid: " . json_encode( $labelOfInvalidType ),
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => json_encode( $labelOfInvalidType ) ]
			),
		];

		$invalidLabel = "invalid \t";
		yield 'invalid label' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_INVALID,
				[
					PropertyLabelValidator::CONTEXT_LABEL => $invalidLabel,
					PropertyLabelValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID,
				"Changed label for 'en' is invalid: $invalidLabel",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => $invalidLabel ]
			),
		];

		yield 'invalid label language code' => [
			$mockSyntaxValidator,
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_FIELD => 'labels',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
				"Not a valid language code 'e2' in changed labels",
				[ UseCaseError::CONTEXT_LANGUAGE => 'e2' ]
			),
		];

		yield 'same value for label and description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
				'Label and description for language code en can not have the same value.',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label duplication' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DUPLICATE,
				[
					PropertyLabelValidator::CONTEXT_LANGUAGE => 'en',
					PropertyLabelValidator::CONTEXT_LABEL => 'en-label',
					PropertyLabelValidator::CONTEXT_CONFLICTING_PROPERTY_ID => 'P123',
				]
			),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE,
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_CONFLICTING_PROPERTY_ID => 'P123' ]
			),
		];
	}

	public function descriptionsValidationErrorProvider(): Generator {
		$mockSyntaxValidator = function ( self $test ) {
			$test->descriptionsSyntaxValidator = $this->createMock( DescriptionsSyntaxValidator::class );
			return $test->descriptionsSyntaxValidator;
		};
		$mockContentsValidator = function ( self $test ) {
			$test->descriptionsContentsValidator = $this->createMock( PropertyDescriptionsContentsValidator::class );
			return $test->descriptionsContentsValidator;
		};

		$invalidDescriptions = [ 'not an associative array' ];
		yield 'invalid descriptions' => [
			$mockSyntaxValidator,
			new ValidationError( DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
				"Invalid input for 'descriptions' in the patched property",
				[
					UseCaseError::CONTEXT_PATH => 'descriptions',
					UseCaseError::CONTEXT_VALUE => $invalidDescriptions,
				]
			),
			[ 'descriptions' => $invalidDescriptions ],
		];

		yield 'empty description' => [
			$mockContentsValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_EMPTY,
				"Changed description for 'en' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		$longDescription = str_repeat( 'a', 51 );
		$maxLength = 50;
		yield 'description too long' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_TOO_LONG,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $longDescription,
					PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					PropertyDescriptionValidator::CONTEXT_LIMIT => $maxLength,
				]
			),
			UseCaseError::newValueTooLong( '/descriptions/en', $maxLength, true ),
		];

		$descriptionOfInvalidType = [ 'invalid', 'description', 'type' ];
		yield 'invalid description type' => [
			$mockSyntaxValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE,
				[
					DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION => $descriptionOfInvalidType,
					DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID,
				"Changed description for 'en' is invalid: " . json_encode( $descriptionOfInvalidType ),
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => json_encode( $descriptionOfInvalidType ) ]
			),
		];

		$invalidDescription = "invalid \t";
		yield 'invalid description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_INVALID,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $invalidDescription,
					PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID,
				"Changed description for 'en' is invalid: $invalidDescription",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => $invalidDescription ]
			),
		];

		yield 'invalid description language code' => [
			$mockSyntaxValidator,
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_FIELD => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
				"Not a valid language code 'e2' in changed descriptions",
				[ UseCaseError::CONTEXT_LANGUAGE => 'e2' ]
			),
		];

		yield 'same value for description and description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
				'Label and description for language code en can not have the same value.',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
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
		$this->aliasesInLanguageValidator = $aliasesInLanguageValidator;
		$originalProperty = new Property( new NumericPropertyId( 'P123' ), new Fingerprint(), 'string' );

		$propertySerialization = [
			'id' => 'P123',
			'type' => 'property',
			'data_type' => 'string',
			'labels' => [ 'en' => 'english-label' ],
			'aliases' => $patchedAliases,
		];

		try {
			$this->newValidator()->validateAndDeserialize( $propertySerialization, $originalProperty );
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
			UseCaseError::newValueTooLong( '/aliases/en/0', self::LIMIT, true ),
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
				UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
				"Invalid input for 'aliases' in the patched property",
				[
					UseCaseError::CONTEXT_PATH => 'aliases',
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

	/**
	 * @dataProvider statementsProvider
	 */
	public function testStatementsValidation( array $patchedStatements, UseCaseError $expectedError ): void {
		$originalProperty = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint(),
			'string',
			new StatementList(
				NewStatement::someValueFor( 'P123' )
					->withGuid( self::EXISTING_STATEMENT_ID )
					->build()
			)
		);

		$propertySerialization = [
			'id' => 'P123',
			'type' => 'property',
			'data_type' => 'string',
			'labels' => [ 'en' => 'english-label' ],
			'statements' => $patchedStatements,
		];

		try {
			$this->newValidator()->validateAndDeserialize( $propertySerialization, $originalProperty );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function statementsProvider(): Generator {
		yield 'Statements not associative' => [
			[ 1, 2, 3 ],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
				"Invalid input for 'statements' in the patched property",
				[
					UseCaseError::CONTEXT_PATH => 'statements',
					UseCaseError::CONTEXT_VALUE => [ 1, 2, 3 ],
				]
			),
		];

		yield 'Invalid statement group type' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[0] => [ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_STATEMENT_GROUP_TYPE,
				'Not a valid statement group',
				[ UseCaseError::CONTEXT_PATH => self::EXISTING_STRING_PROPERTY_IDS[0] ]
			),
		];

		yield 'Invalid statement type: statement not an array' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[1] => [ 'not a valid statement' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
				'Not a valid statement type',
				[ UseCaseError::CONTEXT_PATH => self::EXISTING_STRING_PROPERTY_IDS[1] . '/0' ]
			),
		];

		yield 'Invalid statement type: statement not an associative array' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[1] => [ [ 'not a valid statement' ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
				'Not a valid statement type',
				[ UseCaseError::CONTEXT_PATH => self::EXISTING_STRING_PROPERTY_IDS[1] . '/0' ]
			),
		];

		yield 'Invalid statement field' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[2] => [ [ 'rank' => 'bad rank' ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
				"Invalid input for 'rank' in the patched statement",
				[ UseCaseError::CONTEXT_PATH => 'rank', UseCaseError::CONTEXT_VALUE => 'bad rank' ]
			),
		];

		yield 'Missing statement field' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[3] => [ [ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ] ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
				'Mandatory field missing in the patched statement: value',
				[ UseCaseError::CONTEXT_PATH => 'value' ]
			),
		];

		yield 'Property id mismatch' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[0] => [ [ 'property' => [ 'id' => 'P122' ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
				"Statement's Property ID does not match the statement group key",
				[
					UseCaseError::CONTEXT_PATH => self::EXISTING_STRING_PROPERTY_IDS[0] . '/0/property/id',
					UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => self::EXISTING_STRING_PROPERTY_IDS[0],
					UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => 'P122',
				]
			),
		];

		$statementWithId = [
			'id' => 'P123$4YY2B0D8-BEC1-4D30-B88E-347E08AFD987',
			'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'Statement IDs not modifiable or provided for new statements' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[1] => [ $statementWithId ] ],
			new UseCaseError(
				UseCaseError::STATEMENT_ID_NOT_MODIFIABLE,
				'Statement IDs cannot be created or modified',
				[ UseCaseError::CONTEXT_STATEMENT_ID => $statementWithId[ 'id' ] ]
			),
		];

		$duplicateStatement = [
			'id' => 'P123$5FF2B0D8-BEC1-4D30-B88E-347E08AFD659',
			'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'Duplicate Statement id' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[2] => [ $duplicateStatement, $duplicateStatement ] ],
			new UseCaseError(
				UseCaseError::STATEMENT_ID_NOT_MODIFIABLE,
				'Statement IDs cannot be created or modified',
				[ UseCaseError::CONTEXT_STATEMENT_ID => $duplicateStatement[ 'id' ] ]
			),
		];

		$statementWithExistingId = [
			'id' => self::EXISTING_STATEMENT_ID,
			'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'Property IDs modified' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[3] => [ $statementWithExistingId ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_PROPERTY_NOT_MODIFIABLE,
				'Property of a statement cannot be modified',
				[
					UseCaseError::CONTEXT_STATEMENT_ID => $statementWithExistingId[ 'id' ],
					UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => 'P123',
				]
			),
		];
	}

	private function newValidator(): PatchedPropertyValidator {
		$deserializerFactory = new TestPropertyValuePairDeserializerFactory();
		$deserializerFactory->setDataTypeForProperties( array_fill_keys( self::EXISTING_STRING_PROPERTY_IDS, 'string' ) );

		return new PatchedPropertyValidator(
			$this->labelsSyntaxValidator,
			$this->labelsContentsValidator,
			$this->descriptionsSyntaxValidator,
			$this->descriptionsContentsValidator,
			new AliasesValidator(
				$this->aliasesInLanguageValidator,
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'fr' ] ) ),
				new AliasesDeserializer(),
			),
			new StatementsValidator(
				new StatementValidator(
					new StatementDeserializer(
						$deserializerFactory->createPropertyValuePairDeserializer(),
						$this->createStub( ReferenceDeserializer::class )
					)
				)
			)
		);
	}

}
