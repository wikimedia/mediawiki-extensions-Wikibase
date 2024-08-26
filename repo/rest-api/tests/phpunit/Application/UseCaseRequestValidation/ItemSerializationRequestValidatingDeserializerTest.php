<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

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
	 * In contrast to the solitary testGivenInvalidRequest_throws() method, this is a sociable test.
	 * It is more thorough at testing the behaviour of the SUT and is expected to be more resilient to refactoring of the SUT.
	 * I suggest that if other parts of the SUT are touched, we also refactor the related tests to this more sociable style.
	 *
	 * @dataProvider provideInvalidAliases
	 */
	public function testGivenInvalidRequest_throwsUseCaseError( UseCaseError $expectedError, array $serialization ): void {
		try {
			$this->newRequestValidatingDeserializer()->validateAndDeserialize(
				new CreateItemRequest( $serialization, [], false, null, null )
			);
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function provideInvalidAliases(): Generator {
		yield 'invalid serialization - string' => [
			UseCaseError::newInvalidValue( '/item/aliases' ),
			[ 'aliases' => 'not an array' ],
		];

		yield 'invalid serialization - sequential array' => [
			UseCaseError::newInvalidValue( '/item/aliases' ),
			[ 'aliases' => [ 'not', 'an', 'associative', 'array' ] ],
		];

		yield 'invalid language code - int' => [
			UseCaseError::newInvalidKey( '/item/aliases', '3912' ),
			[ 'aliases' => [ 3912 => [ 'alias 1' ] ] ],
		];

		yield 'invalid language code - not an allowed language code' => [
			UseCaseError::newInvalidKey( '/item/aliases', 'xyz' ),
			[ 'aliases' => [ 'xyz' => [ 'alias 1' ] ] ],
		];

		yield 'invalid aliases in language - string' => [
			UseCaseError::newInvalidValue( '/item/aliases/en' ),
			[ 'aliases' => [ 'en' => 'not a list' ] ],
		];

		yield 'invalid aliases in language - associative array' => [
			UseCaseError::newInvalidValue( '/item/aliases/en' ),
			[ 'aliases' => [ 'en' => [ 'not' => 'a', 'sequential' => 'array' ] ] ],
		];

		yield 'invalid aliases in language - empty array' => [
			UseCaseError::newInvalidValue( '/item/aliases/en' ),
			[ 'aliases' => [ 'en' => [] ] ],
		];

		yield 'invalid alias - integer' => [
			UseCaseError::newInvalidValue( '/item/aliases/en/0' ),
			[ 'aliases' => [ 'en' => [ 7940, 'alias 2' ] ] ],
		];

		yield 'invalid alias - zero length string' => [
			UseCaseError::newInvalidValue( '/item/aliases/en/1' ),
			[ 'aliases' => [ 'en' => [ 'alias 1', '' ] ] ],
		];

		yield 'invalid alias - whitespace only' => [
			UseCaseError::newInvalidValue( '/item/aliases/en/0' ),
			[ 'aliases' => [ 'en' => [ "  \t  ", 'alias 2' ] ] ],
		];

		yield 'invalid alias - invalid characters' => [
			UseCaseError::newInvalidValue( '/item/aliases/en/1' ),
			[ 'aliases' => [ 'en' => [ 'alias 1', "alias \t with \t tabs" ] ] ],
		];

		yield 'invalid alias - too long' => [
			UseCaseError::newValueTooLong( '/item/aliases/en/0', self::MAX_LENGTH ),
			[ 'aliases' => [ 'en' => [ str_repeat( 'A', self::MAX_LENGTH + 1 ) ] ] ],
		];
	}

	private function newRequestValidatingDeserializer(): ItemSerializationRequestValidatingDeserializer {
		$validLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];
		return new ItemSerializationRequestValidatingDeserializer(
			new ItemValidator(
				$this->createStub( LabelsSyntaxValidator::class ),
				$this->createStub( ItemLabelsContentsValidator::class ),
				$this->createStub( DescriptionsSyntaxValidator::class ),
				$this->createStub( ItemDescriptionsContentsValidator::class ),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator(
						new TermValidatorFactory(
							self::MAX_LENGTH,
							$validLanguageCodes,
							$this->createStub( EntityIdParser::class ),
							$this->createStub( TermsCollisionDetectorFactory::class ),
							$this->createStub( TermLookup::class ),
							$this->createStub( LanguageNameUtils::class )
						)
					),
					new ValueValidatorLanguageCodeValidator( new MembershipValidator( $validLanguageCodes ) ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				$this->createStub( StatementsValidator::class ),
				$this->createStub( SitelinksValidator::class ),
			)
		);
	}

	/**
	 * @dataProvider itemValidationErrorProvider
	 * @dataProvider itemLabelsValidationErrorProvider
	 * @dataProvider itemDescriptionsValidationErrorProvider
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
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/some-field'",
				[
					UseCaseError::CONTEXT_PATH => '/item/some-field',
				]
			),
		];
	}

	public function itemLabelsValidationErrorProvider(): Generator {
		$invalidLabels = [ 'not an associative array' ];
		yield 'invalid labels' => [
			new ValidationError( LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE ),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/labels'",
				[ UseCaseError::CONTEXT_PATH => '/item/labels' ]
			),
			[ 'labels' => $invalidLabels ],
		];
		yield 'empty label' => [
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newInvalidValue( '/item/labels/en' ),
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
			UseCaseError::newValueTooLong( '/item/labels/en', self::MAX_LENGTH ),
		];

		yield 'invalid label type' => [
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_LABEL => [ 'invalid', 'label', 'type' ],
					LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			UseCaseError::newInvalidValue( '/item/labels/en' ),
		];

		yield 'invalid label' => [
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[
					ItemLabelValidator::CONTEXT_LABEL => "invalid \t",
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			UseCaseError::newInvalidValue( '/item/labels/en' ),
		];

		yield 'invalid label language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_FIELD => 'labels',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newInvalidKey( '/item/labels', 'e2' ),
		];

		yield 'same value for label and description' => [
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
				]
			),
		];
	}

	public function itemDescriptionsValidationErrorProvider(): Generator {
		$invalidDescriptions = [ 'not a valid descriptions array' ];
		yield 'invalid descriptions' => [
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE,
				[ DescriptionsSyntaxValidator::CONTEXT_VALUE => $invalidDescriptions ]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/descriptions'",
				[ UseCaseError::CONTEXT_PATH => '/item/descriptions' ]
			),
			[ 'descriptions' => $invalidDescriptions ],
		];
		yield 'empty description' => [
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			UseCaseError::newInvalidValue( '/item/descriptions/en' ),
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
			UseCaseError::newValueTooLong( '/item/descriptions/en', self::MAX_LENGTH ),
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
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/descriptions/en'",
				[ UseCaseError::CONTEXT_PATH => '/item/descriptions/en' ]
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
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/descriptions/en'",
				[ UseCaseError::CONTEXT_PATH => '/item/descriptions/en' ]
			),
		];
		yield 'invalid description language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_FIELD => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'e2',
				]
			),
			UseCaseError::newInvalidKey( '/item/descriptions', 'e2' ),
		];

		yield 'same value for description and label ' => [
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
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LABEL => 'en-label',
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemDescriptionValidator::CONTEXT_CONFLICTING_ITEM_ID => 'Q123',
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

	public function itemStatementsValidationErrorProvider(): Generator {
		$invalidStatements = [ 'not valid statements' ];
		yield 'invalid statements array' => [
			new ValidationError(
				StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE,
				[
					StatementsValidator::CONTEXT_PATH => '/item/statements',
					StatementsValidator::CONTEXT_VALUE => $invalidStatements,
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/statements'",
				[ UseCaseError::CONTEXT_PATH => '/item/statements' ]
			),
		];

		yield 'statement group not sequential' => [
			new ValidationError(
				StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
				[ StatementsValidator::CONTEXT_PATH => '/item/statements/P1' ]
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
				[ StatementsValidator::CONTEXT_PATH => '/item/statements/P1/0' ]
			),
			UseCaseError::newInvalidValue( '/item/statements/P1/0' ),
		];

		yield 'missing statement field' => [
			new ValidationError(
				StatementValidator::CODE_MISSING_FIELD,
				[
					StatementValidator::CONTEXT_PATH => '/item/statements/P1/0',
					StatementValidator::CONTEXT_FIELD => 'value',
				]
			),
			UseCaseError::newMissingField( '/item/statements/P1/0', 'value' ),
		];

		yield 'invalid statement field' => [
			new ValidationError(
				StatementValidator::CODE_INVALID_FIELD,
				[
					StatementValidator::CONTEXT_PATH => '/item/statements/P1/0/value',
					StatementValidator::CONTEXT_FIELD => 'value',
					StatementValidator::CONTEXT_VALUE => 'invalid-value',
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
			UseCaseError::newInvalidValue( "/item/sitelinks/$site" ),
		];
		yield SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE => [
			new ValidationError( SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE, [
				SitelinksValidator::CONTEXT_SITE_ID => $site,
			] ),
			new UseCaseError(
				UseCaseError::INVALID_VALUE,
				"Invalid value at '/item/sitelinks'",
				[ UseCaseError::CONTEXT_PATH => '/item/sitelinks' ]
			),
			[ 'sitelinks' => [ [ 'title' => 'Whatever' ] ] ],
		];
		$invalidSiteId = 'invalid-site-id';
		yield SiteIdValidator::CODE_INVALID_SITE_ID => [
			new ValidationError( SiteIdValidator::CODE_INVALID_SITE_ID, [
				SiteIdValidator::CONTEXT_SITE_ID_VALUE => $invalidSiteId,
			] ),
			UseCaseError::newInvalidKey( '/item/sitelinks', $invalidSiteId ),
		];
		yield SitelinkValidator::CODE_TITLE_MISSING => [
			new ValidationError( SitelinkValidator::CODE_TITLE_MISSING, [
				SitelinkValidator::CONTEXT_PATH => "/item/sitelinks/$site",
			] ),
			UseCaseError::newMissingField( "/item/sitelinks/$site", 'title' ),
		];
		yield SitelinkValidator::CODE_EMPTY_TITLE => [
			new ValidationError( SitelinkValidator::CODE_EMPTY_TITLE, [
				SitelinkValidator::CONTEXT_PATH => "/item/sitelinks/$site/title",
				SitelinkValidator::CONTEXT_VALUE => '',
			] ),
			UseCaseError::newInvalidValue( "/item/sitelinks/$site/title" ),
		];
		yield SitelinkValidator::CODE_INVALID_TITLE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE, [
				SitelinkValidator::CONTEXT_PATH => "/item/sitelinks/$site/title",
			] ),
			UseCaseError::newInvalidValue( "/item/sitelinks/$site/title" ),
		];
		yield SitelinkValidator::CODE_INVALID_FIELD_TYPE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_FIELD_TYPE, [
				SitelinkValidator::CONTEXT_PATH => "/item/sitelinks/$site/title",
			] ),
			UseCaseError::newInvalidValue( "/item/sitelinks/$site/title" ),
		];
		yield SitelinkValidator::CODE_INVALID_BADGE => [
			new ValidationError( SitelinkValidator::CODE_INVALID_BADGE, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
				SitelinkValidator::CONTEXT_VALUE => 'P3',
			] ),
			UseCaseError::newInvalidValue( "/item/sitelinks/$site/badges/1" ),
			[ 'sitelinks' => [ $site => [ 'title' => 'Whatever', 'badges' => [ 'Q12', 'P3' ] ] ] ],
		];
		yield SitelinkValidator::CODE_BADGE_NOT_ALLOWED => [
			new ValidationError( SitelinkValidator::CODE_BADGE_NOT_ALLOWED, [
				SitelinkValidator::CONTEXT_SITE_ID => $site,
				SitelinkValidator::CONTEXT_VALUE => 'Q3',
			] ),
			UseCaseError::newInvalidValue( "/item/sitelinks/$site/badges/2" ),
			[ 'sitelinks' => [ $site => [ 'title' => 'Whatever', 'badges' => [ 'Q1', 'Q2', 'Q3' ] ] ] ],
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
				SitelinkValidator::CONTEXT_CONFLICTING_ITEM_ID => 'Q666',
			] ),
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
				[
					UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => 'Q666',
					UseCaseError::CONTEXT_SITE_ID => $site,
				]
			),
		];
	}

}
