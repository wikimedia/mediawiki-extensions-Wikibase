<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItem;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemValidatorTest extends TestCase {

	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesInLanguageValidator $aliasesInLanguageValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsSyntaxValidator = new LabelsSyntaxValidator(
			new LabelsDeserializer(),
			new LanguageCodeValidator( [ 'ar', 'de', 'en' ] )
		);
		$this->descriptionsSyntaxValidator = new DescriptionsSyntaxValidator(
			new DescriptionsDeserializer(),
			new LanguageCodeValidator( [ 'ar', 'de', 'en' ] )
		);
		$this->labelsContentsValidator = new ItemLabelsContentsValidator(
			$this->createStub( ItemLabelValidator::class )
		);
		$this->descriptionsContentsValidator = new ItemDescriptionsContentsValidator(
			$this->createStub( ItemDescriptionValidator::class )
		);
		$this->aliasesInLanguageValidator = $this->createStub( AliasesInLanguageValidator::class );
	}

	private const LIMIT = 40;
	private const ALLOWED_BADGES = [ 'Q999' ];

	/**
	 * @dataProvider patchedItemProvider
	 */
	public function testValid( array $patchedItemSerialization, Item $expectedPatchedItem ): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$this->assertEquals(
			$expectedPatchedItem,
			$this->newValidator()->validateAndDeserialize( $patchedItemSerialization, $originalItem )
		);
	}

	public static function patchedItemProvider(): Generator {
		yield 'minimal item' => [
			[ 'id' => 'Q123' ],
			new Item( new ItemId( 'Q123' ) ),
		];
		yield 'item with all fields' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => [ 'en' => 'english-label' ],
				'descriptions' => [ 'en' => 'english-description' ],
				'aliases' => [ 'en' => [ 'english-alias' ] ],
				'sitelinks' => [ 'enwiki' => [ 'title' => 'potato' ] ],
				'statements' => [
					'P321' => [
						[
							'property' => [ 'id' => 'P321' ],
							'value' => [ 'type' => 'somevalue' ],
						],
					],
				],
			],
			new Item(
				new ItemId( 'Q123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
					new TermList( [ new Term( 'en', 'english-description' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'english-alias' ] ) ] )
				),
				new SitelinkList( [ new SiteLink( 'enwiki', 'potato' ) ] ),
				new StatementList( NewStatement::someValueFor( 'P321' )->build() )
			),
		];
	}

	public function testIgnoresItemIdRemoval(): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$patchedItem = [
			'type' => 'item',
			'labels' => [ 'en' => 'potato' ],
		];

		$validatedItem = $this->newValidator()->validateAndDeserialize( $patchedItem, $originalItem );

		$this->assertEquals( $originalItem->getId(), $validatedItem->getId() );
	}

	/**
	 * @dataProvider topLevelValidationProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedItem, Exception $expectedError ): void {
		$originalItem = new Item(
			new ItemId( 'Q123' ),
			new Fingerprint(),
		);

		try {
			$this->newValidator()->validateAndDeserialize( $patchedItem, $originalItem );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function topLevelValidationProvider(): Generator {
		yield 'unexpected field' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => [ 'de' => 'Kartoffel' ],
				'foo' => 'bar',
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_UNEXPECTED_FIELD,
				"The patched item contains an unexpected field: 'foo'"
			),
		];

		yield 'invalid field' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => 'invalid-labels',
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'labels' in the patched item",
				[ UseCaseError::CONTEXT_PATH => 'labels', UseCaseError::CONTEXT_VALUE => 'invalid-labels' ]
			),
		];

		yield "Illegal modification 'id' field" => [
			[
				'id' => 'Q12',
				'type' => 'item',
				'labels' => [ 'en' => 'potato' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID,
				'Cannot change the ID of the existing item'
			),
		];
	}

	public function testValidateOnlyModifiedLabels(): void {
		$itemId = new ItemId( 'Q13' );

		$originalItem = NewItem::withId( $itemId )
			->andLabel( 'en', 'spud' )
			->andLabel( 'de', 'Kartoffel' )
			->build();

		$patchedItem = [
			'id' => "$itemId",
			'type' => 'item',
			'labels' => [ 'en' => 'potato', 'de' => 'Kartoffel', 'ar' => 'بطاطا' ], // only 'en' and 'ar' labels have been patched
		];

		$inputTerms = new PartiallyValidatedLabels( [
			new Term( 'en', 'potato' ),
			new Term( 'de', 'Kartoffel' ),
			new Term( 'ar', 'بطاطا' ),
		] );
		$termsToCompareWith = new PartiallyValidatedDescriptions();

		// expect validation only for the modified labels
		$this->labelsContentsValidator = $this->createMock( ItemLabelsContentsValidator::class );

		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $inputTerms, $termsToCompareWith, [ 'en', 'ar' ] )
			->willReturn( null );

		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'getValidatedLabels' )
			->willReturn( $inputTerms->asPlainTermList() );

		$this->assertEquals(
			new Item(
				$itemId,
				new Fingerprint( $inputTerms->asPlainTermList(), $termsToCompareWith->asPlainTermList(), null )
			),
			$this->newValidator()
				->validateAndDeserialize( $patchedItem, $originalItem )
		);
	}

	public function testValidateOnlyModifiedDescriptions(): void {
		$itemId = new ItemId( 'Q13' );

		$originalItem = NewItem::withId( $itemId )
			->andDescription( 'en', 'en-description' )
			->andDescription( 'de', 'de-description' )
			->build();

		$patchedItem = [
			'id' => "$itemId",
			'type' => 'item',
			'descriptions' => [ 'en' => 'updated-en-description', 'de' => 'de-description', 'ar' => 'ar-description' ],
		];

		$inputTerms = new PartiallyValidatedDescriptions( [
			new Term( 'en', 'updated-en-description' ),
			new Term( 'de', 'de-description' ),
			new Term( 'ar', 'ar-description' ),
		] );
		$termsToCompareWith = new PartiallyValidatedLabels();

		// expect validation only for the modified descriptions
		$this->descriptionsContentsValidator = $this->createMock( ItemDescriptionsContentsValidator::class );

		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $inputTerms, $termsToCompareWith, [ 'en', 'ar' ] )
			->willReturn( null );

		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'getValidatedDescriptions' )
			->willReturn( $inputTerms->asPlainTermList() );

		$this->assertEquals(
			new Item(
				$itemId,
				new Fingerprint( $termsToCompareWith->asPlainTermList(), $inputTerms->asPlainTermList(), null ),
			),
			$this->newValidator()->validateAndDeserialize( $patchedItem, $originalItem )
		);
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
				array_merge( [ 'id' => 'Q123' ], $patchedSerialization ), new Item( new ItemId( 'Q123' ) )
			);
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
			$test->labelsContentsValidator = $this->createMock( ItemLabelsContentsValidator::class );
			return $test->labelsContentsValidator;
		};

		$invalidLabels = [ 'not an associative array' ];
		yield 'invalid labels' => [
			$mockSyntaxValidator,
			new ValidationError( LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'labels' in the patched item",
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
				ItemLabelValidator::CODE_TOO_LONG,
				[
					ItemLabelValidator::CONTEXT_LABEL => $longLabel,
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LIMIT => $maxLength,
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_LABEL_TOO_LONG,
				"Changed label for 'en' must not be more than $maxLength characters long",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $longLabel,
					UseCaseError::CONTEXT_CHARACTER_LIMIT => $maxLength,
				]
			),
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
				ItemLabelValidator::CODE_INVALID,
				[
					ItemLabelValidator::CONTEXT_LABEL => $invalidLabel,
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
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
					LanguageCodeValidator::CONTEXT_PATH => 'labels',
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
				ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language code 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'label and description duplication' => [
			$mockContentsValidator,
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LABEL => 'en-label',
					ItemLabelValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
				"Item 'Q123' already has label 'en-label' associated with language code 'en', using the same description text",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_LABEL => 'en-label',
					UseCaseError::CONTEXT_DESCRIPTION => 'en-description',
					UseCaseError::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
		];
	}

	public function descriptionsValidationErrorProvider(): Generator {
		$mockSyntaxValidator = function ( self $test ) {
			$test->descriptionsSyntaxValidator = $this->createMock( DescriptionsSyntaxValidator::class );
			return $test->descriptionsSyntaxValidator;
		};
		$mockContentsValidator = function ( self $test ) {
			$test->descriptionsContentsValidator = $this->createMock( ItemDescriptionsContentsValidator::class );
			return $test->descriptionsContentsValidator;
		};

		$invalidDescriptions = [ 'not an associative array' ];
		yield 'invalid descriptions' => [
			$mockSyntaxValidator,
			new ValidationError( DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'descriptions' in the patched item",
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
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $longDescription,
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LIMIT => $maxLength,
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,
				"Changed description for 'en' must not be more than $maxLength characters long",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $longDescription,
					UseCaseError::CONTEXT_CHARACTER_LIMIT => $maxLength,
				]
			),
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
				ItemDescriptionValidator::CODE_INVALID,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $invalidDescription,
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
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
					LanguageCodeValidator::CONTEXT_PATH => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
				"Not a valid language code 'e2' in changed descriptions",
				[ UseCaseError::CONTEXT_LANGUAGE => 'e2' ]
			),
		];

		yield 'same value for description and label' => [
			$mockContentsValidator,
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language code 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'description and label duplication' => [
			$mockContentsValidator,
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LABEL => 'en-label',
					ItemLabelValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
				"Item 'Q123' already has label 'en-label' associated with language code 'en', using the same description text",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_LABEL => 'en-label',
					UseCaseError::CONTEXT_DESCRIPTION => 'en-description',
					UseCaseError::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
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
		$originalItem = new Item( new ItemId( 'Q123' ) );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'aliases' => $patchedAliases,
		];

		try {
			$this->newValidator()->validateAndDeserialize( $itemSerialization, $originalItem );
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
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'aliases' in the patched item",
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

	private function newValidator(): PatchedItemValidator {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);
		return new PatchedItemValidator(
			$this->labelsSyntaxValidator,
			$this->labelsContentsValidator,
			$this->descriptionsSyntaxValidator,
			$this->descriptionsContentsValidator,
			new AliasesValidator(
				$this->aliasesInLanguageValidator,
				new LanguageCodeValidator( [ 'ar', 'de', 'en', 'fr' ] ),
				new AliasesDeserializer(),
			),
			new SitelinkDeserializer(
				'/\?/',
				self::ALLOWED_BADGES,
				new SameTitleSitelinkTargetResolver(),
				new DummyItemRevisionMetaDataRetriever()
			),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

}
