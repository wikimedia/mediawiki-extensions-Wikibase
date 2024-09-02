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
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
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
			$this->newValidator()->validateAndDeserialize(
				$patchedPropertySerialization,
				$originalProperty,
				[ 'id' => 'P123', 'statements' => [] ]
			)
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

		$validatedProperty = $this->newValidator()->validateAndDeserialize(
			$patchedProperty,
			$originalProperty,
			[ 'id' => 'P123', 'statements' => [] ]
		);

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
			$this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty, [ 'id' => 'P123', 'statements' => [] ] )
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
			$this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty, [ 'id' => 'P123', 'statements' => [] ] )
		);
	}

	/**
	 * @dataProvider topLevelValidationErrorProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedProperty, Exception $expectedError ): void {
		$originalProperty = new Property( new NumericPropertyId( 'P123' ), new Fingerprint(), 'string' );

		try {
			$this->newValidator()->validateAndDeserialize( $patchedProperty, $originalProperty, [ 'id' => 'P123', 'statements' => [] ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function topLevelValidationErrorProvider(): Generator {

		yield "missing 'data_type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'labels' => [ 'en' => 'english-label' ],
			],
			UseCaseError::newMissingFieldInPatchResult( '', 'data_type' ),
		];

		yield 'invalid field' => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data_type' => 'string',
				'labels' => 'illegal string',
			],
			UseCaseError::newPatchResultInvalidValue( '/labels', 'illegal string' ),
		];

		yield "Illegal modification 'id' field" => [
			[
				'id' => 'P12',
				'type' => 'property',
				'data_type' => 'string',
				'labels' => [ 'en' => 'english-label' ],
			],
			UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' ),
		];

		yield "Illegal modification 'data_type' field" => [
			[
				'id' => 'P123',
				'type' => 'property',
				'data_type' => 'wikibase-item',
				'labels' => [ 'en' => 'english-label' ],
			],
			UseCaseError::newPatchResultModifiedReadOnlyValue( '/data_type' ),
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
				),
				[ 'id' => 'P123', 'statements' => [] ]
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
			UseCaseError::newPatchResultInvalidValue( '/labels', $invalidLabels ),
			[ 'labels' => $invalidLabels ],
		];

		yield 'empty label' => [
			$mockContentsValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newPatchResultInvalidValue( '/labels/en', '' ),
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
			UseCaseError::newPatchResultInvalidValue( '/labels/en', $labelOfInvalidType ),
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
			UseCaseError::newPatchResultInvalidValue( '/labels/en', $invalidLabel ),
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
			UseCaseError::newPatchResultInvalidKey( '/labels', 'e2' ),
		];

		yield 'same value for label and description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
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
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE,
				[ DescriptionsSyntaxValidator::CONTEXT_VALUE => $invalidDescriptions ]
			),
			UseCaseError::newPatchResultInvalidValue( '/descriptions', $invalidDescriptions ),
			[ 'descriptions' => $invalidDescriptions ],
		];

		yield 'empty description' => [
			$mockContentsValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newPatchResultInvalidValue( '/descriptions/en', '' ),
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
			UseCaseError::newPatchResultInvalidValue( '/descriptions/en', $descriptionOfInvalidType ),
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
			UseCaseError::newPatchResultInvalidValue( '/descriptions/en', $invalidDescription ),
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
			UseCaseError::newPatchResultInvalidKey( '/descriptions', 'e2' ),
		];

		yield 'same value for description and description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
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
			$this->newValidator()->validateAndDeserialize(
				$propertySerialization,
				$originalProperty,
				[ 'id' => 'P123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function aliasesValidationErrorProvider(): Generator {
		yield 'invalid aliases - sequential array' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'not', 'an', 'associative', 'array' ],
			UseCaseError::newPatchResultInvalidValue( '/aliases', [ 'not', 'an', 'associative', 'array' ] ),
		];

		yield 'invalid language code - integer' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 3248 => [ 'alias' ] ],
			UseCaseError::newPatchResultInvalidKey( '/aliases', '3248' ),
		];

		yield 'invalid language code - xyz' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'xyz' => [ 'alias' ] ],
			UseCaseError::newPatchResultInvalidKey( '/aliases', 'xyz' ),
		];

		yield 'invalid language code - empty string' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ '' => [ 'alias' ] ],
			UseCaseError::newPatchResultInvalidKey( '/aliases', '' ),
		];

		yield "invalid 'aliases in language' list - string" => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => 'not a list of aliases in a language' ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/en', 'not a list of aliases in a language' ),
		];

		yield "invalid 'aliases in language' list - associative array" => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => [ 'not' => 'a', 'sequential' => 'array' ] ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/en', [ 'not' => 'a', 'sequential' => 'array' ] ),
		];

		yield "invalid 'aliases in language' list - empty array" => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => [] ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/en', [] ),
		];

		yield 'invalid alias - integer' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'en' => [ 3146, 'second alias' ] ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/en/0', 3146 ),
		];

		yield 'invalid alias - empty string' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'de' => [ '' ] ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/de/0', '' ),
		];

		yield 'invalid alias - only white space' => [
			$this->createStub( AliasesInLanguageValidator::class ),
			[ 'de' => [ " \t " ] ],
			UseCaseError::newPatchResultInvalidValue( '/aliases/de/0', '' ),
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
			UseCaseError::newPatchResultInvalidValue( '/aliases/en/1', $invalidAlias ),
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
			$this->newValidator()->validateAndDeserialize(
				$propertySerialization,
				$originalProperty,
				[ 'id' => 'P123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function statementsProvider(): Generator {
		yield 'Statements not associative' => [
			[ 1, 2, 3 ],
			UseCaseError::newPatchResultInvalidValue( '/statements', [ 1, 2, 3 ] ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[0];
		$invalidStatementGroup = [ 'property' => [ 'id' => $propertyId ] ];
		yield 'Invalid statement group type' =>
		[
			[ $propertyId => $invalidStatementGroup ],
			UseCaseError::newPatchResultInvalidValue( "/statements/$propertyId", $invalidStatementGroup ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[1];
		yield 'Invalid statement type: statement not an array' =>
		[
			[ $propertyId => [ 'not a valid statement' ] ],
			UseCaseError::newPatchResultInvalidValue( "/statements/$propertyId/0", 'not a valid statement' ),
		];

		yield 'Invalid statement type: statement not an associative array' =>
		[
			[ $propertyId => [ [ 'not a valid statement' ] ] ],
			UseCaseError::newPatchResultInvalidValue( "/statements/$propertyId/0", [ 'not a valid statement' ] ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[2];
		yield 'Invalid statement field' =>
		[
			[ $propertyId => [ [ 'rank' => 'bad rank' ] ] ],
			UseCaseError::newPatchResultInvalidValue( "/statements/$propertyId/0/rank", 'bad rank' ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[3];
		yield 'Missing statement field' =>
		[
			[ $propertyId => [ [ 'property' => [ 'id' => $propertyId ] ] ] ],
			UseCaseError::newMissingFieldInPatchResult( "/statements/$propertyId/0", 'value' ),
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

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[1];
		$statementWithId = [
			'id' => 'P123$4YY2B0D8-BEC1-4D30-B88E-347E08AFD987',
			'property' => [ 'id' => $propertyId ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'Statement IDs not modifiable or provided for new statements' =>
		[
			[ $propertyId => [ $statementWithId ] ],
			UseCaseError::newPatchResultModifiedReadOnlyValue( "/statements/$propertyId/0/id" ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[2];
		$duplicateStatement = [
			'id' => 'P123$5FF2B0D8-BEC1-4D30-B88E-347E08AFD659',
			'property' => [ 'id' => $propertyId ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'Duplicate Statement id' =>
		[
			[ $propertyId => [ $duplicateStatement, $duplicateStatement ] ],
			UseCaseError::newPatchResultModifiedReadOnlyValue( "/statements/$propertyId/0/id" ),
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
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
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
