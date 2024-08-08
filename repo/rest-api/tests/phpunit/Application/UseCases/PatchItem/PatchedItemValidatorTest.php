<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItem;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
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
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ItemReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink as SitelinkReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList as Statements;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Helpers\TestPropertyValuePairDeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemValidatorTest extends TestCase {

	private SiteLinkLookup $siteLinkLookup;

	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesInLanguageValidator $aliasesInLanguageValidator;
	private SitelinkTargetTitleResolver $sitelinkTargetTitleResolver;

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
		$this->labelsContentsValidator = new ItemLabelsContentsValidator(
			$this->createStub( ItemLabelValidator::class )
		);
		$this->descriptionsContentsValidator = new ItemDescriptionsContentsValidator(
			$this->createStub( ItemDescriptionValidator::class )
		);
		$this->aliasesInLanguageValidator = $this->createStub( AliasesInLanguageValidator::class );
		$this->sitelinkTargetTitleResolver = new SameTitleSitelinkTargetResolver();
		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
	}

	private const LIMIT = 40;
	private const ALLOWED_BADGES = [ 'Q999', 'Q777' ];
	private const EXISTING_STATEMENT_ID = 'Q123$5FF2B0D8-BEC1-4D30-B88E-347E08AFD659';
	private const EXISTING_STRING_PROPERTY_IDS = [ 'P1359', 'P3874', 'P2304', 'P6411' ];

	/**
	 * @dataProvider patchedItemProvider
	 */
	public function testValid( array $patchedItemSerialization, Item $expectedPatchedItem ): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$item = $this->createStub( ItemReadModel::class );

		$this->assertEquals(
			$expectedPatchedItem,
			$this->newValidator()->validateAndDeserialize(
				$item,
				$patchedItemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			)
		);
	}

	public static function patchedItemProvider(): Generator {
		yield 'minimal item' => [
			[ 'id' => 'Q123' ],
			new Item( new ItemId( 'Q123' ) ),
		];

		$siteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		yield 'item with all fields' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => [ 'en' => 'english-label' ],
				'descriptions' => [ 'en' => 'english-description' ],
				'aliases' => [ 'en' => [ 'english-alias' ] ],
				'sitelinks' => [ $siteId => [ 'title' => 'potato' ] ],
				'statements' => [
					self::EXISTING_STRING_PROPERTY_IDS[0] => [
						[
							'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
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
				new SitelinkList( [ new SiteLink( $siteId, 'potato' ) ] ),
				new StatementList( NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[0] )->build() )
			),
		];
	}

	public function testIgnoresItemIdRemoval(): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$item = $this->createStub( ItemReadModel::class );

		$patchedItem = [
			'type' => 'item',
			'labels' => [ 'en' => 'potato' ],
		];

		$validatedItem = $this->newValidator()->validateAndDeserialize(
			$item,
			$patchedItem,
			$originalItem,
			[ 'id' => 'Q123', 'statements' => [] ]
		);

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

		$item = $this->createStub( ItemReadModel::class );

		try {
			$this->newValidator()->validateAndDeserialize( $item, $patchedItem, $originalItem, [ 'id' => 'Q123', 'statements' => [] ] );
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

		$item = $this->createStub( ItemReadModel::class );

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
				->validateAndDeserialize( $item, $patchedItem, $originalItem, [ 'id' => "$itemId", 'statements' => [] ] )
		);
	}

	public function testValidateOnlyModifiedDescriptions(): void {
		$itemId = new ItemId( 'Q13' );

		$originalItem = NewItem::withId( $itemId )
			->andDescription( 'en', 'en-description' )
			->andDescription( 'de', 'de-description' )
			->build();

		$item = $this->createStub( ItemReadModel::class );

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
			$this->newValidator()->validateAndDeserialize( $item, $patchedItem, $originalItem, [ 'id' => "$itemId", 'statements' => [] ] )
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

		$item = $this->createStub( ItemReadModel::class );

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				array_merge( [ 'id' => 'Q123' ], $patchedSerialization ),
				new Item( new ItemId( 'Q123' ) ),
				[ 'id' => 'Q123', 'statements' => [] ]
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
					LanguageCodeValidator::CONTEXT_FIELD => 'labels',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newPatchResultInvalidKey( '/labels', 'e2' ),
		];
		yield 'same value for label and description' => [
			$mockContentsValidator,
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
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
					ItemLabelValidator::CONTEXT_CONFLICTING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::DATA_POLICY_VIOLATION,
				'Edit violates data policy',
				[
					UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
						UseCaseError::CONTEXT_LANGUAGE => 'en',
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => 'Q123',
					],
				],
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

		$maxLength = 50;
		yield 'description too long' => [
			$mockContentsValidator,
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => str_repeat( 'a', 51 ),
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LIMIT => $maxLength,
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
					LanguageCodeValidator::CONTEXT_FIELD => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newPatchResultInvalidKey( '/descriptions', 'e2' ),
		];

		yield 'same value for description and label' => [
			$mockContentsValidator,
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
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
					ItemLabelValidator::CONTEXT_CONFLICTING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::DATA_POLICY_VIOLATION,
				'Edit violates data policy',
				[
					UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
						UseCaseError::CONTEXT_LANGUAGE => 'en',
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => 'Q123',
					],
				],
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

		$item = $this->createStub( ItemReadModel::class );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'aliases' => $patchedAliases,
		];

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$itemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
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

	/**
	 * @dataProvider providePatchInvalidStatements
	 */
	public function testStatementsValidation( array $patchedStatements, UseCaseError $expectedError ): void {
		$originalItem = NewItem::withId( 'Q123' )->andStatement(
			NewStatement::someValueFor( 'P789' )->withGuid( self::EXISTING_STATEMENT_ID )->build()
		)->build();

		$item = $this->createStub( ItemReadModel::class );

		$patchedItemSerialization = [ 'id' => 'Q123', 'type' => 'item', 'statements' => $patchedStatements ];

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$patchedItemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function providePatchInvalidStatements(): Generator {
		$propertyId = self::EXISTING_STRING_PROPERTY_IDS[1];
		yield 'statements not an associative array' => [
			[ 1, 2, 3 ],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'statements' in the patched item",
				[
					UseCaseError::CONTEXT_PATH => 'statements',
					UseCaseError::CONTEXT_VALUE => [ 1, 2, 3 ],
				]
			),
		];

		yield 'invalid statement group type' => [
			[ $propertyId => [ 'property' => [ 'id' => $propertyId ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_STATEMENT_GROUP_TYPE,
				'Not a valid statement group',
				[ UseCaseError::CONTEXT_PATH => $propertyId ]
			),
		];

		yield 'invalid statement type: statement not an array' => [
			[ $propertyId => [ [ 'property' => [ 'id' => $propertyId ], 'value' => [ 'type' => 'somevalue' ] ], 'invalid' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
				'Not a valid statement type',
				[ UseCaseError::CONTEXT_PATH => "$propertyId/1" ]
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

		yield 'missing field in statement' => [
			[ $propertyId => [ [ 'property' => [ 'id' => $propertyId ] ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
				'Mandatory field missing in the patched statement: value',
				[ UseCaseError::CONTEXT_PATH => 'value' ]
			),
		];

		$invalidStatement = [ 'rank' => 'bad rank', 'property' => [ 'id' => $propertyId ], 'value' => [ 'type' => 'novalue' ] ];
		yield 'invalid field in statement' => [
			[ $propertyId => [ $invalidStatement ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
				"Invalid input for 'rank' in the patched statement",
				[ UseCaseError::CONTEXT_PATH => 'rank', UseCaseError::CONTEXT_VALUE => 'bad rank' ]
			),
		];

		$propertyIdKey = self::EXISTING_STRING_PROPERTY_IDS[2];
		$propertyIdValue = self::EXISTING_STRING_PROPERTY_IDS[3];
		yield 'property id mismatch' => [
			[ $propertyIdKey => [ [ 'property' => [ 'id' => $propertyIdValue ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
				"Statement's Property ID does not match the statement group key",
				[
					UseCaseError::CONTEXT_PATH => "$propertyIdKey/0/property/id",
					UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $propertyIdKey,
					UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $propertyIdValue,
				]
			),
		];
	}

	/**
	 * @dataProvider sitelinksValidationErrorProvider
	 */
	public function testSitelinksValidation(
		array $patchedSitelinks,
		Exception $expectedError
	): void {
		$originalItem = new Item( new ItemId( 'Q123' ) );

		$item = $this->createStub( ItemReadModel::class );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'sitelinks' => $patchedSitelinks,
		];

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$itemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function sitelinksValidationErrorProvider(): Generator {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$badgeItemId = new ItemId( self::ALLOWED_BADGES[ 0 ] );

		yield 'invalid sitelink type' => [
			[ $validSiteId => 'invalid-sitelink' ],
			new UseCaseError(
				UseCaseError::PATCHED_INVALID_SITELINK_TYPE,
				'Not a valid sitelink type in patched sitelinks',
				[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
			),
		];

		$invalidSitelinks = [ 'invalid-sitelinks' ];
		yield 'sitelinks not associative' => [
			$invalidSitelinks,
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'sitelinks' in the patched item",
				[
					UseCaseError::CONTEXT_PATH => 'sitelinks',
					UseCaseError::CONTEXT_VALUE => $invalidSitelinks,
				]
			),
		];

		yield 'invalid site id' => [
			[ 'bad-site-id' => [ 'title' => 'test_title' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_SITE_ID,
				"Not a valid site ID 'bad-site-id' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => 'bad-site-id' ]
			),
		];

		yield 'missing title' => [
			[ $validSiteId => [ 'badges' => [ $badgeItemId ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_MISSING_TITLE,
				"No sitelink title provided for site '$validSiteId' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
			),
		];

		yield 'empty title' => [
			[ $validSiteId => [ 'title' => '' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
				"Sitelink cannot be empty for site '$validSiteId' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
			),
		];

		$invalidTitle = 'invalid??%00';
		yield 'invalid title' => [
			[ $validSiteId => [ 'title' => $invalidTitle ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
				"Invalid sitelink title '$invalidTitle' for site '$validSiteId' in patched sitelinks",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_TITLE => $invalidTitle,
				]
			),
		];

		yield 'invalid badges format' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => $badgeItemId ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
				"Badges value for site '$validSiteId' is not a list in patched sitelinks",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGES => $badgeItemId,
				]
			),
		];

		$invalidBadge = 'not-an-item-id';
		yield 'invalid badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ $invalidBadge ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_BADGE,
				"Incorrect patched sitelinks. Badge value '$invalidBadge' for site '$validSiteId' is not an item ID",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGE => $invalidBadge,
				]
			),
		];

		$itemIdNotBadge = new ItemId( 'Q99' );
		yield 'item is not a badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ "$itemIdNotBadge" ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE,
				"Incorrect patched sitelinks. Item 'Q99' used for site '$validSiteId' is not allowed as a badge",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGE => 'Q99',
				]
			),
		];
	}

	/**
	 * @dataProvider modifiedSitelinksProvider
	 */
	public function testValidatesOnlyModifiedSitelinks(
		Sitelinks $originalSitelinks,
		array $patchedSitelinks,
		array $expectedValidatedSitelinkSites
	): void {
		$itemId = new ItemId( 'Q13' );

		$originalItem = NewItem::withId( $itemId )
			->andLabel( 'en', 'spud' )
			->andLabel( 'de', 'Kartoffel' )
			->build();

		$item = $this->createStub( ItemReadModel::class );
		$item->method( 'getSitelinks' )->willReturn( $originalSitelinks );

		$patchedItem = [
			'id' => "$itemId",
			'type' => 'item',
			'labels' => [ 'en' => 'potato' ],
			'sitelinks' => $patchedSitelinks,
		];

		$this->siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$this->siteLinkLookup->expects( $this->exactly( count( $expectedValidatedSitelinkSites ) ) )
			->method( 'getItemIdForSiteLink' )
			->willReturnCallback( function ( SiteLink $sitelink ) use ( $expectedValidatedSitelinkSites ): void {
				$this->assertContains( $sitelink->getSiteId(), $expectedValidatedSitelinkSites );
			} );

		$this->assertInstanceOf( Item::class, $this->newValidator()->validateAndDeserialize(
			$item,
			$patchedItem,
			$originalItem,
			[ 'id' => 'Q123', 'statements' => [] ]
		) );
	}

	public function modifiedSitelinksProvider(): Generator {
		$originalSitelinks = new Sitelinks(
			new SitelinkReadModel(
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0],
				'Potato',
				[ new ItemId( self::ALLOWED_BADGES[0] ) ],
				''
			),
			new SitelinkReadModel( TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1], 'Kartoffel', [], '' ),
		);

		yield 'new sitelink' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato', 'badges' => [ self::ALLOWED_BADGES[0] ] ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Kartoffel' ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[2] => [ 'title' => 'بطاطا' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[2] ],
		];

		yield 'modified sitelink title' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato', 'badges' => [ self::ALLOWED_BADGES[0] ] ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Erdapfel' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] ],
		];

		yield 'modified sitelink badges' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato', 'badges' => self::ALLOWED_BADGES ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Kartoffel' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] ],
		];
	}

	public function testTitleDoesNotExist_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

		$originalItem = new Item( new ItemId( 'Q123' ) );

		$item = $this->createStub( ItemReadModel::class );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'sitelinks' => [ $validSiteId => [ 'title' => 'non-existing-title' ] ],
		];

		$this->sitelinkTargetTitleResolver = $this->createStub( SameTitleSitelinkTargetResolver::class );
		$this->sitelinkTargetTitleResolver->method( 'resolveTitle' )->willThrowException(
			$this->createStub( SitelinkTargetNotFound::class )
		);

		$expectedError = new UseCaseError(
			UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
			"Incorrect patched sitelinks. Page with title 'non-existing-title' does not exist on site '$validSiteId'",
			[
				UseCaseError::CONTEXT_SITE_ID => $validSiteId,
				UseCaseError::CONTEXT_TITLE => 'non-existing-title',
			]
		);

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$itemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function testSitelinkConflict_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$conflictingItemId = 'Q987';

		$originalItem = new Item( new ItemId( 'Q123' ) );

		$item = $this->createStub( ItemReadModel::class );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'sitelinks' => [ $validSiteId => [ 'title' => 'test-title' ] ],
		];

		$this->siteLinkLookup->method( 'getItemIdForSiteLink' )->willReturn( new ItemId( $conflictingItemId ) );

		$expectedError = UseCaseError::newDataPolicyViolation(
			UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
			[
				UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				UseCaseError::CONTEXT_SITE_ID => $validSiteId,
			]
		);

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$itemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function testSitelinkUrlModification_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$title = 'test_title';

		$originalItem = new Item(
			new ItemId( 'Q123' ),
			new Fingerprint(),
			new SitelinkList( [ new SiteLink( $validSiteId, $title ) ] ),
		);

		$item = new ItemReadModel(
			new ItemId( 'Q123' ),
			new Labels(),
			new Descriptions(),
			new Aliases(),
			new Sitelinks( new SitelinkReadModel( $validSiteId, $title, [], 'https://en.wikipedia.org/wiki/Example.com' ) ),
			new Statements()
		);

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'sitelinks' => [ $validSiteId => [ 'title' => $title, 'url' => 'https://en.wikipedia.org/wiki/.example' ] ],
		];

		$expectedError = new UseCaseError(
			UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE,
			'URL of sitelink cannot be modified',
			[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
		);

		try {
			$this->newValidator()->validateAndDeserialize(
				$item,
				$itemSerialization,
				$originalItem,
				[ 'id' => 'Q123', 'statements' => [] ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedError, $error );
		}
	}

	private function newValidator(): PatchedItemValidator {
		$deserializerFactory = new TestPropertyValuePairDeserializerFactory();
		$deserializerFactory->setDataTypeForProperties( array_fill_keys( self::EXISTING_STRING_PROPERTY_IDS, 'string' ) );

		return new PatchedItemValidator(
			$this->labelsSyntaxValidator,
			$this->labelsContentsValidator,
			$this->descriptionsSyntaxValidator,
			$this->descriptionsContentsValidator,
			new AliasesValidator(
				$this->aliasesInLanguageValidator,
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'fr' ] ) ),
				new AliasesDeserializer(),
			),
			new SitelinksValidator(
				new SiteIdValidator( TestValidatingRequestDeserializer::ALLOWED_SITE_IDS ),
				new SiteLinkLookupSitelinkValidator(
					new SitelinkDeserializer(
						'/\?/',
						self::ALLOWED_BADGES,
						$this->sitelinkTargetTitleResolver,
						new DummyItemRevisionMetaDataRetriever()
					),
					$this->siteLinkLookup
				)
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
