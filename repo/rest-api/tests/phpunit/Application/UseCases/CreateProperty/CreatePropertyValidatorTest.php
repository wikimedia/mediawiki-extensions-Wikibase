<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateProperty;

use Exception;
use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Tests\RestApi\Helpers\TestPropertyValuePairDeserializerFactory;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidatorTest extends TestCase {

	public const MAX_LENGTH = 50;
	private const EXISTING_STRING_PROPERTY_IDS = [ 'P3685', 'P3177', 'P6920', 'P4877' ];

	private EditMetadataRequestValidatingDeserializer $editMetadataValidator;
	private array $dataTypesArray;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private PropertyLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private PropertyDescriptionsContentsValidator $descriptionsContentsValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->editMetadataValidator = $this->createStub( EditMetadataRequestValidatingDeserializer::class );
		$this->dataTypesArray = [ 'wikibase-item', 'wikibase-property', 'string' ];
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
	}

	public function testGivenValidRequest_returnsDeserializedRequest(): void {
		$propertySerialization = [ 'data_type' => 'string' ];
		$request = new CreatePropertyRequest( $propertySerialization, [], false, null, null );

		$expectedProperty = new Property( null, null, 'string' );

		$this->assertEquals( $expectedProperty, $this->newValidator()->validateAndDeserialize( $request )->getProperty() );
	}

	/**
	 * @dataProvider invalidPropertyProvider
	 */
	public function testGivenInvalidPropertySerialization_throws( array $serialization, UseCaseError $expectedError ): void {
		$request = new CreatePropertyRequest( $serialization, [], false, null, null );

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidPropertyProvider(): Generator {
		yield 'missing data type' => [
			[],
			UseCaseError::newMissingField( '/property', 'data_type' ),
		];
		yield 'invalid data_type field type' => [
			[ 'data_type' => 123 ],
			UseCaseError::newInvalidValue( '/property/data_type' ),
		];
		yield 'invalid labels field type' => [
			[ 'data_type' => 'string', 'labels' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/labels' ),
		];
		yield 'invalid descriptions field type' => [
			[ 'data_type' => 'string', 'descriptions' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/descriptions' ),
		];
		yield 'invalid aliases field type' => [
			[ 'data_type' => 'string', 'aliases' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/aliases' ),
		];
		yield 'invalid statements field type' => [
			[ 'data_type' => 'string', 'statements' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/statements' ),
		];
		yield 'invalid data_type field' => [
			[ 'data_type' => 'invalid_type' ],
			UseCaseError::newInvalidValue( '/property/data_type' ),
		];
	}

	public function testGivenInvalidEditMetadata_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$request = new CreatePropertyRequest( [ 'data_type' => 'string' ], [ 'tag1', 'tag2' ], false, 'edit comment', 'SomeUser' );

		$this->editMetadataValidator = $this->createMock( EditMetadataRequestValidatingDeserializer::class );
		$this->editMetadataValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willThrowException( $expectedException );

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider labelsValidationErrorProvider
	 * @dataProvider descriptionsValidationErrorProvider
	 *
	 */
	public function testGivenValidationErrorInField_throws(
		callable $getFieldValidator,
		ValidationError $validationError,
		UseCaseError $expectedError
	): void {
		$request = new CreatePropertyRequest( [ 'data_type' => 'string' ], [ 'tag1', 'tag2' ], false, 'edit comment', 'SomeUser' );
		$getFieldValidator( $this )->expects( $this->once() )->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function labelsValidationErrorProvider(): Generator {
		$mockSyntaxValidator = function( self $test ) {
			$test->labelsSyntaxValidator = $this->createMock( LabelsSyntaxValidator::class );
			return $test->labelsSyntaxValidator;
		};
		$mockContentsValidator = function( self $test ) {
			$test->labelsContentsValidator = $this->createMock( PropertyLabelsContentsValidator::class );
			return $test->labelsContentsValidator;
		};

		$invalidLabels = [ 'not an associative array' ];
		yield 'invalid labels' => [
			$mockSyntaxValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE,
				[ LabelsSyntaxValidator::CONTEXT_VALUE => $invalidLabels ]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/property/labels'",
				[ UseCaseError::CONTEXT_PATH => '/property/labels' ]
			),
		];
		yield 'empty label' => [
			$mockSyntaxValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newInvalidValue( '/property/labels/en' ),
		];
		yield 'label too long' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_TOO_LONG,
				[
					PropertyLabelValidator::CONTEXT_LABEL => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					PropertyLabelValidator::CONTEXT_LANGUAGE => 'en',
					PropertyLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			UseCaseError::newValueTooLong( '/property/labels/en', self::MAX_LENGTH ),
		];
		yield 'invalid label type' => [
			$mockSyntaxValidator,
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_LABEL => [ 'invalid', 'label', 'type' ],
					LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			UseCaseError::newInvalidValue( '/property/labels/en' ),
		];
		yield 'invalid label' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyLabelValidator::CODE_INVALID,
				[
					PropertyLabelValidator::CONTEXT_LABEL => "invalid \t",
					PropertyLabelValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			UseCaseError::newInvalidValue( '/property/labels/en' ),
		];
		yield 'invalid label language code' => [
			$mockSyntaxValidator,
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH => '/property/labels',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newInvalidKey( '/property/labels', 'e2' ),
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
			new UseCaseError(
				UseCaseError::DATA_POLICY_VIOLATION,
				'Edit violates data policy',
				[
					UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE,
					UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
						UseCaseError::CONTEXT_LANGUAGE => 'en',
						UseCaseError::CONTEXT_CONFLICTING_PROPERTY_ID => 'P123',
					],
				]
			),
		];
	}

	public function descriptionsValidationErrorProvider(): Generator {
		$mockSyntaxValidator = function( self $test ) {
			$test->descriptionsSyntaxValidator = $this->createMock( DescriptionsSyntaxValidator::class );
			return $test->descriptionsSyntaxValidator;
		};
		$mockContentsValidator = function( self $test ) {
			$test->descriptionsContentsValidator = $this->createMock( PropertyDescriptionsContentsValidator::class );
			return $test->descriptionsContentsValidator;
		};

		$invalidDescriptions = [ 'not a valid descriptions array' ];
		yield 'invalid descriptions' => [
			$mockSyntaxValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE,
				[ DescriptionsSyntaxValidator::CONTEXT_VALUE => $invalidDescriptions ]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/property/descriptions'",
				[ UseCaseError::CONTEXT_PATH => '/property/descriptions' ]
			),
		];
		yield 'empty description' => [
			$mockContentsValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newInvalidValue( '/property/descriptions/en' ),
		];
		yield 'description too long' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_TOO_LONG,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					PropertyDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			UseCaseError::newValueTooLong( '/property/descriptions/en', self::MAX_LENGTH ),
		];
		yield 'invalid description type' => [
			$mockSyntaxValidator,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE,
				[
					DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION => 22,
					DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/property/descriptions/en'",
				[ UseCaseError::CONTEXT_PATH => '/property/descriptions/en' ]
			),
		];
		yield 'invalid description' => [
			$mockContentsValidator,
			new ValidationError(
				PropertyDescriptionValidator::CODE_INVALID,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => "invalid \t",
					PropertyDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/property/descriptions/en'",
				[ UseCaseError::CONTEXT_PATH => '/property/descriptions/en' ]
			),
		];
		yield 'invalid description language code' => [
			$mockSyntaxValidator,
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH => '/property/descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newInvalidKey( '/property/descriptions', 'e2' ),
		];
		yield 'same value for description and label' => [
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
	public function testAliasesValidation( array $aliases, Exception $expectedError ): void {
		$request = new CreatePropertyRequest(
			[ 'data_type' => 'string', 'aliases' => $aliases ],
			[ 'tag1', 'tag2' ],
			false,
			'edit comment',
			'SomeUser'
		);

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function aliasesValidationErrorProvider(): Generator {
		yield 'invalid aliases - sequential array' => [
			[ 'not', 'an', 'associative', 'array' ],
			UseCaseError::newInvalidValue( '/property/aliases' ),
		];
		yield 'invalid language code - int' => [
			[ 3248 => [ 'alias' ] ],
			UseCaseError::newInvalidKey( '/property/aliases', '3248' ),
		];
		yield 'invalid language code - xyz' => [
			[ 'xyz' => [ 'alias' ] ],
			UseCaseError::newInvalidKey( '/property/aliases', 'xyz' ),
		];
		yield 'invalid language code - empty string' => [
			[ '' => [ 'alias' ] ],
			UseCaseError::newInvalidKey( '/property/aliases', '' ),
		];
		yield "invalid 'aliases in language' list - string" => [
			[ 'en' => 'not a list of aliases in a language' ],
			UseCaseError::newInvalidValue( '/property/aliases/en' ),
		];
		yield "invalid 'aliases in language' list - associative array" => [
			[ 'en' => [ 'not' => 'a', 'sequential' => 'array' ] ],
			UseCaseError::newInvalidValue( '/property/aliases/en' ),
		];
		yield "invalid 'aliases in language' list - empty array" => [
			[ 'en' => [] ],
			UseCaseError::newInvalidValue( '/property/aliases/en' ),
		];
		yield 'invalid alias - int' => [
			[ 'en' => [ 3146, 'second alias' ] ],
			UseCaseError::newInvalidValue( '/property/aliases/en/0' ),
		];
		yield 'invalid alias - empty string' => [
			[ 'de' => [ '' ] ],
			UseCaseError::newInvalidValue( '/property/aliases/de/0' ),
		];
		yield 'invalid alias - only white space' => [
			[ 'de' => [ " \t " ] ],
			UseCaseError::newInvalidValue( '/property/aliases/de/0' ),
		];
		yield 'alias too long' => [
			[ 'en' => [ 'this alias is too long for the configured limit which is 50 char' ] ],
			UseCaseError::newValueTooLong( '/property/aliases/en/0', self::MAX_LENGTH ),
		];
		yield 'alias contains invalid character' => [
			[ 'en' => [ 'valid alias', "tabs \t not \t allowed" ] ],
			UseCaseError::newInvalidValue( '/property/aliases/en/1' ),
		];
	}

	/**
	 * @dataProvider statementsValidationErrorProvider
	 */
	public function testStatementsValidation( array $statements, Exception $expectedError ): void {
		$request = new CreatePropertyRequest(
			[ 'data_type' => 'string', 'statements' => $statements ],
			[ 'tag1', 'tag2' ],
			false,
			'edit comment',
			'SomeUser'
		);

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function statementsValidationErrorProvider(): Generator {
		$invalidStatements = [ 'not valid statements' ];
		yield 'statements not associative' => [
			$invalidStatements,
			UseCaseError::newInvalidValue( '/property/statements' ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[0];
		$invalidStatementGroup = [ 'property' => [ 'id' => $propertyId ] ];
		yield 'statement group not sequential' => [
			[ $propertyId => $invalidStatementGroup ],
			UseCaseError::newInvalidValue( "/property/statements/$propertyId" ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[1];
		yield 'invalid statement type: statement not an array' => [
			[ $propertyId => [ 'not a valid statement' ] ],
			UseCaseError::newInvalidValue( "/property/statements/$propertyId/0" ),
		];

		yield 'Invalid statement type: statement not an associative array' =>
		[
			[ $propertyId => [ [ 'not a valid statement' ] ] ],
			UseCaseError::newInvalidValue( "/property/statements/$propertyId/0" ),
		];

		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[2];
		yield 'Invalid statement field' =>
		[
			[ $propertyId => [ [ 'rank' => 'bad rank' ] ] ],
			UseCaseError::newInvalidValue( "/property/statements/$propertyId/0/rank" ),
		];

		yield 'missing statement field' => [
			[ $propertyId => [ [ 'property' => [ 'id' => $propertyId ] ] ] ],
			UseCaseError::newMissingField( "/property/statements/$propertyId/0", 'value' ),
		];

		$nonExistingPropertyId = 'P9999999';
		yield 'property does not exist' => [
			[ $nonExistingPropertyId => [ [ 'property' => [ 'id' => $nonExistingPropertyId ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			UseCaseError::newReferencedResourceNotFound( "/property/statements/$nonExistingPropertyId/0/property/id" ),
		];

		yield 'Property id mismatch' =>
		[
			[ self::EXISTING_STRING_PROPERTY_IDS[0] => [ [ 'property' => [ 'id' => 'P122' ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new UseCaseError(
				UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
				"Statement's Property ID does not match the Statement group key",
				[
					UseCaseError::CONTEXT_PATH => '/property/statements/' . self::EXISTING_STRING_PROPERTY_IDS[0] . '/0/property/id',
					UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => self::EXISTING_STRING_PROPERTY_IDS[0],
					UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => 'P122',
				]
			),
		];
	}

	private function newValidator(): CreatePropertyValidator {
		$deserializerFactory = new TestPropertyValuePairDeserializerFactory();
		$deserializerFactory->setDataTypeForProperties( array_fill_keys( self::EXISTING_STRING_PROPERTY_IDS, 'string' ) );
		$allowedLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];

		return new CreatePropertyValidator(
			$this->editMetadataValidator,
			$this->dataTypesArray,
			$this->labelsSyntaxValidator,
			$this->labelsContentsValidator,
			$this->descriptionsSyntaxValidator,
			$this->descriptionsContentsValidator,
			new AliasesValidator(
				new TermValidatorFactoryAliasesInLanguageValidator(
					new TermValidatorFactory(
						self::MAX_LENGTH,
						$allowedLanguageCodes,
						$this->createStub( EntityIdParser::class ),
						$this->createStub( TermsCollisionDetectorFactory::class ),
						WikibaseRepo::getTermLookup(),
						$this->createStub( LanguageNameUtils::class )
					)
				),
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( $allowedLanguageCodes ) ),
				new AliasesDeserializer( new AliasesInLanguageDeserializer() )
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
