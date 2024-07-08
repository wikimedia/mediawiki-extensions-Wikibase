<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemSerializationRequestValidatingDeserializerTest extends TestCase {

	public const MAX_LENGTH = 50;

	public function testGivenValidRequest_returnsItem(): void {
		$request = $this->createStub( ItemSerializationRequest::class );
		$request->method( 'getItem' )->willReturn( [ 'labels' => [ 'en' => 'English label' ] ] );
		$expectedItem = NewItem::withLabel( 'en', 'English label' )->build();
		$itemValidator = $this->createStub( ItemValidator::class );
		$itemValidator->method( 'getValidatedItem' )->willReturn( $expectedItem );

		$this->assertEquals(
			$expectedItem,
			( new ItemSerializationRequestValidatingDeserializer( $itemValidator ) )->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider itemValidationErrorProvider
	 * @dataProvider itemLabelsValidationErrorProvider
	 * @dataProvider itemDescriptionsValidationErrorProvider
	 * @dataProvider itemAliasesValidationErrorProvider
	 * @dataProvider itemStatementsValidationErrorProvider
	 * @dataProvider sitelinksValidationErrorProvider
	 */
	public function testGivenInvalidRequest_throws(
		ValidationError $validationError,
		UseCaseError $expectedError,
		array $itemSerialization = [ 'item serialization stub' ]
	): void {
		$request = $this->createStub( ItemSerializationRequest::class );
		$request->method( 'getItem' )->willReturn( $itemSerialization );

		$itemValidator = $this->createMock( ItemValidator::class );
		$itemValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $itemSerialization )
			->willReturn( $validationError );

		try {
			( new ItemSerializationRequestValidatingDeserializer( $itemValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertEquals( $expectedError, $useCaseEx );
		}
	}

	public function itemValidationErrorProvider(): Generator {
		yield 'invalid field' => [
			new ValidationError(
				ItemValidator::CODE_INVALID_FIELD,
				[
					ItemValidator::CONTEXT_FIELD => 'some-field',
					ItemValidator::CONTEXT_VALUE => 'some-value',
				]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'some-field'",
				[
					UseCaseError::CONTEXT_PATH => 'some-field',
					UseCaseError::CONTEXT_VALUE => 'some-value',
				]
			),
		];

		yield 'unexpected field' => [
			new ValidationError(
				ItemValidator::CODE_UNEXPECTED_FIELD,
				[ ItemValidator::CONTEXT_FIELD => 'some-field' ]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_UNEXPECTED_FIELD,
				'The request body contains an unexpected field',
				[ UseCaseError::CONTEXT_FIELD => 'some-field' ]
			),
		];
	}

	public function itemLabelsValidationErrorProvider(): Generator {
		$invalidLabels = [ 'not an associative array' ];
		yield 'invalid labels' => [
			new ValidationError( LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'labels'",
				[
					UseCaseError::CONTEXT_PATH => 'labels',
					UseCaseError::CONTEXT_VALUE => $invalidLabels,
				]
			),
			[ 'labels' => $invalidLabels ],
		];
		yield 'empty label' => [
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_EMPTY,
				'Label must not be empty',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label too long' => [
			new ValidationError(
				ItemLabelValidator::CODE_TOO_LONG,
				[
					ItemLabelValidator::CONTEXT_LABEL => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			new UseCaseError(
				UseCaseError::LABEL_TOO_LONG,
				'Label must be no more than 50 characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_CHARACTER_LIMIT => 50,
				]
			),
		];

		yield 'invalid label type' => [
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_LABEL => [ 'invalid', 'label', 'type' ],
					LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LABEL,
				'Not a valid label: ["invalid","label","type"]',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'invalid label' => [
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[
					ItemLabelValidator::CONTEXT_LABEL => "invalid \t",
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LABEL,
				"Not a valid label: invalid \t",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'invalid label language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH => ItemValidator::CONTEXT_LABELS,
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => ItemValidator::CONTEXT_LABELS,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];

		yield 'same value for label and description' => [
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label and description duplication' => [
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
				UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
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

	public function itemDescriptionsValidationErrorProvider(): Generator {
		$invalidDescriptions = [ 'not a valid descriptions array' ];
		yield 'invalid descriptions' => [
			new ValidationError( DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'descriptions'",
				[
					UseCaseError::CONTEXT_PATH => 'descriptions',
					UseCaseError::CONTEXT_VALUE => $invalidDescriptions,
				]
			),
			[ 'descriptions' => $invalidDescriptions ],
		];
		yield 'empty description' => [
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::DESCRIPTION_EMPTY,
				'Description must not be empty',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'description too long' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			new UseCaseError(
				UseCaseError::DESCRIPTION_TOO_LONG,
				'Description must be no more than 50 characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_CHARACTER_LIMIT => 50,
				]
			),
		];
		yield 'invalid description type' => [
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE,
				[
					DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION => 22,
					DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_DESCRIPTION,
				'Not a valid description: 22',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'invalid description' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => "invalid \t",
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_DESCRIPTION,
				"Not a valid description: invalid \t",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'invalid description language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH => ItemValidator::CONTEXT_DESCRIPTIONS,
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => ItemValidator::CONTEXT_DESCRIPTIONS,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];

		yield 'same value for description and label ' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'description and label duplication' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LABEL => 'en-label',
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
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

	public function itemAliasesValidationErrorProvider(): Generator {
		yield 'empty alias' => [
			new ValidationError(
				AliasesValidator::CODE_EMPTY_ALIAS,
				[ AliasesValidator::CONTEXT_PATH => '/en/1' ]
			),
			UseCaseError::newInvalidValue( '/item/aliases/en/1' ),
		];

		yield 'empty aliases in language list' => [
			new ValidationError(
				AliasesValidator::CODE_EMPTY_ALIAS_LIST,
				[ AliasesValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::ALIAS_LIST_EMPTY,
				'Alias list must not be empty',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		$invalidAliases = [ 'not a valid aliases array' ];
		yield 'invalid aliases' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIASES,
				[ AliasesValidator::CONTEXT_ALIASES => $invalidAliases ]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'aliases'",
				[ UseCaseError::CONTEXT_PATH => 'aliases', UseCaseError::CONTEXT_VALUE => $invalidAliases ]
			),
		];

		yield 'invalid aliases in language list' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIAS_LIST,
				[ AliasesValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::INVALID_ALIAS_LIST,
				'Not a valid alias list',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'alias too long' => [
			new ValidationError(
				AliasesValidator::CODE_TOO_LONG_ALIAS,
				[
					AliasesValidator::CONTEXT_ALIAS => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					AliasesValidator::CONTEXT_LANGUAGE => 'en',
					AliasesValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			new UseCaseError(
				UseCaseError::ALIAS_TOO_LONG,
				'Alias must be no more than 50 characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_CHARACTER_LIMIT => 50,
				]
			),
		];

		yield 'invalid alias deserialization' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIAS,
				[
					AliasesValidator::CONTEXT_ALIAS => 22,
					AliasesValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_ALIAS,
				'Not a valid alias: 22',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'invalid alias' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIAS,
				[
					AliasesValidator::CONTEXT_ALIAS => "invalid \t",
					AliasesValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_ALIAS,
				"Not a valid alias: invalid \t",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'duplicated alias' => [
			new ValidationError(
				AliasesValidator::CODE_DUPLICATE_ALIAS,
				[ AliasesValidator::CONTEXT_LANGUAGE => 'en', AliasesValidator::CONTEXT_ALIAS => 'duplicated-alias' ]
			),
			new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: 'duplicated-alias'",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_ALIAS => 'duplicated-alias' ]
			),
		];

		yield 'invalid aliases language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH => AliasesValidator::CONTEXT_ALIAS,
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => AliasesValidator::CONTEXT_ALIAS,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];
	}

	public function itemStatementsValidationErrorProvider(): Generator {
		$invalidStatements = [ 'not valid statements' ];
		yield 'invalid statements array' => [
			new ValidationError(
				StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE,
				[
					StatementsValidator::CONTEXT_PATH => 'statements',
					StatementsValidator::CONTEXT_STATEMENTS => $invalidStatements,
				]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'statements'",
				[ UseCaseError::CONTEXT_PATH => 'statements', UseCaseError::CONTEXT_VALUE => $invalidStatements ]
			),
		];

		yield 'statement group not sequential' => [
			new ValidationError(
				StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
				[ StatementsValidator::CONTEXT_PATH => 'P1' ]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/statements/P1'",
				[ UseCaseError::CONTEXT_PATH => '/item/statements/P1' ]
			),
		];

		yield 'invalid statement type' => [
			new ValidationError(
				StatementsValidator::CODE_STATEMENT_NOT_ARRAY,
				[ StatementsValidator::CONTEXT_PATH => 'P1/0' ]
			),
			UseCaseError::newInvalidValue( '/item/statements/P1/0' ),
		];

		yield 'missing statement field' => [
			new ValidationError(
				StatementsValidator::CODE_MISSING_STATEMENT_DATA,
				[
					StatementsValidator::CONTEXT_PATH => '/P1/0',
					StatementsValidator::CONTEXT_FIELD => 'value',
				]
			),
			new UseCaseError(
				UseCaseError::STATEMENT_DATA_MISSING_FIELD,
				'Mandatory field missing in the statement data: value',
				[ UseCaseError::CONTEXT_PATH => '/P1/0', UseCaseError::CONTEXT_FIELD => 'value' ]
			),
		];

		yield 'invalid statement field' => [
			new ValidationError(
				StatementsValidator::CODE_INVALID_STATEMENT_DATA,
				[
					StatementsValidator::CONTEXT_PATH => 'P1/0/value',
					StatementsValidator::CONTEXT_FIELD => 'value',
					StatementsValidator::CONTEXT_VALUE => 'invalid-value',
				]
			),
			UseCaseError::newInvalidValue( '/item/statements/P1/0/value' ),
		];
	}

	public static function sitelinksValidationErrorProvider(): Generator {
		$site = 'enwiki';
		yield SitelinksValidator::CODE_INVALID_SITELINK => [
			new ValidationError( SitelinksValidator::CODE_INVALID_SITELINK, [
				SitelinksValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::INVALID_SITELINK_TYPE,
				'Not a valid sitelink type',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
		];
		yield SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE => [
			new ValidationError( SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE, [
				SitelinksValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'sitelinks'",
				[
					UseCaseError::CONTEXT_PATH => 'sitelinks',
					UseCaseError::CONTEXT_VALUE => [ [ 'title' => 'Whatever' ] ],
				]
			),
			[ 'sitelinks' => [ [ 'title' => 'Whatever' ] ] ],
		];
		yield SiteIdValidator::CODE_INVALID_SITE_ID => [
			new ValidationError( SiteIdValidator::CODE_INVALID_SITE_ID, [
				SiteIdValidator::CONTEXT_SITE_ID_VALUE => 'invalid-site-id',
			] ),
			UseCaseError::newInvalidValue( '/item/sitelinks/invalid-site-id' ),
		];
		yield SitelinkValidator::CODE_TITLE_MISSING => [
			new ValidationError( SitelinkValidator::CODE_TITLE_MISSING, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::SITELINK_DATA_MISSING_TITLE,
				'Mandatory sitelink title missing',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
		];
		yield SitelinkValidator::CODE_EMPTY_TITLE => [
			new ValidationError( SitelinkValidator::CODE_EMPTY_TITLE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::TITLE_FIELD_EMPTY,
				'Title must not be empty',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
		];
		yield SitelinkValidator::CODE_INVALID_TITLE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::INVALID_TITLE_FIELD,
				'Not a valid input for title field',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
		];
		yield SitelinkValidator::CODE_INVALID_TITLE_TYPE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE_TYPE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::INVALID_TITLE_FIELD,
				'Not a valid input for title field',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
		];
		yield SitelinkValidator::CODE_INVALID_BADGES_TYPE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_BADGES_TYPE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::INVALID_SITELINK_BADGES_FORMAT,
				'Value of badges field is not a list',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
			[ 'sitelinks' => [ $site => [ 'title' => 'Whatever', 'badges' => 'not-a-list' ] ] ],
		];
		yield SitelinkValidator::CODE_INVALID_BADGE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_BADGE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
				SitelinkValidator::CONTEXT_BADGE => 'P3',
			] ),
			new UseCaseError(
				UseCaseError::INVALID_INPUT_SITELINK_BADGE,
				'Badge input is not an item ID: P3',
				[
					UseCaseError::CONTEXT_SITE_ID => $site,
					UseCaseError::CONTEXT_BADGE => 'P3',
				]
			),
		];
		yield SitelinkValidator::CODE_BADGE_NOT_ALLOWED => [
			new ValidationError( SitelinkValidator::CODE_BADGE_NOT_ALLOWED, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
				SitelinkValidator::CONTEXT_BADGE => 'Q1',
			] ),
			new UseCaseError(
				UseCaseError::ITEM_NOT_A_BADGE,
				'Item ID provided as badge is not allowed as a badge: Q1',
				[
					UseCaseError::CONTEXT_SITE_ID => $site,
					UseCaseError::CONTEXT_BADGE => 'Q1',
				]
			),
		];
		yield SitelinkValidator::CODE_TITLE_NOT_FOUND => [
			new ValidationError( SitelinkValidator::CODE_TITLE_NOT_FOUND, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::SITELINK_TITLE_NOT_FOUND,
				'Page with title Whatever does not exist on the given site',
				[ UseCaseError::CONTEXT_SITE_ID => $site ]
			),
			[ 'sitelinks' => [ $site => [ 'title' => 'Whatever' ] ] ],
		];
		yield SitelinkValidator::CODE_SITELINK_CONFLICT => [
			new ValidationError( SitelinkValidator::CODE_SITELINK_CONFLICT, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
				SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID => 'Q666',
			] ),
			new UseCaseError(
				UseCaseError::SITELINK_CONFLICT,
				'Sitelink is already being used on Q666',
				[
					UseCaseError::CONTEXT_MATCHING_ITEM_ID => 'Q666',
					UseCaseError::CONTEXT_SITE_ID => $site,
				]
			),
		];
	}

}
