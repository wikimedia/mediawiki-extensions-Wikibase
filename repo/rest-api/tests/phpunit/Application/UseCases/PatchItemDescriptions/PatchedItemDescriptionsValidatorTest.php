<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemDescriptions;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchedItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchedItemDescriptionsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemDescriptionsValidatorTest extends TestCase {

	private ItemDescriptionValidator $descriptionValidator;
	private DescriptionLanguageCodeValidator $languageCodeValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->descriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$this->languageCodeValidator = $this->createStub( DescriptionLanguageCodeValidator::class );
	}

	/**
	 * @dataProvider validDescriptionsProvider
	 */
	public function testWithValidDescriptions( array $descriptionsSerialization, TermList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), $descriptionsSerialization )
		);
	}

	public static function validDescriptionsProvider(): Generator {
		yield 'no descriptions' => [
			[],
			new TermList(),
		];
		yield 'valid descriptions' => [
			[ 'en' => 'description', 'de' => 'Beschreibung' ],
			new TermList( [ new Term( 'en', 'description' ), new Term( 'de', 'Beschreibung' ) ] ),
		];
	}

	public function testValidateOnlyModifiedDescriptions(): void {
		$originalLabels = new TermList();
		$originalDescriptions = new TermList( [
			new Term( 'en', 'description to change' ),
			new Term( 'de', 'Beschreibung' ),
		] );

		// only 'en' and 'bar' descriptions have been patched
		$patchedDescriptions = [ 'en' => 'description', 'de' => 'Beschreibung', 'ar' => 'وصف' ];

		// expect validation only for the modified descriptions
		$this->descriptionValidator = $this->createMock( ItemDescriptionValidator::class );
		$expectedArgs = [
			[ 'en', 'description', $originalLabels ],
			[ 'ar', 'وصف', $originalLabels ],
		];
		$this->descriptionValidator->expects( $this->exactly( 2 ) )
			->method( 'validate' )
			->willReturnCallback( function ( $language, $description, $labels ) use ( &$expectedArgs ) {
				$curExpectedArgs = array_shift( $expectedArgs );
				$this->assertSame( $curExpectedArgs[0], $language );
				$this->assertSame( $curExpectedArgs[1], $description );
				$this->assertSame( $curExpectedArgs[2], $labels );
				return null;
			} );

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'description' ),
				new Term( 'de', 'Beschreibung' ),
				new Term( 'ar', 'وصف' ),
			] ),
			$this->newValidator()->validateAndDeserialize( $originalDescriptions, $originalLabels, $patchedDescriptions )
		);
	}

	/**
	 * @dataProvider invalidDescriptionsProvider
	 */
	public function testWithInvalidDescriptions(
		array $descriptionsSerialization,
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = null
	): void {
		$this->descriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$this->descriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), $descriptionsSerialization );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertEquals( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidDescriptionsProvider(): Generator {
		$invalidDescriptions = [ 'not', 'an', 'associative', 'array' ];
		yield 'invalid descriptions - sequential array' => [
			$invalidDescriptions,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE,
				[ DescriptionsSyntaxValidator::CONTEXT_VALUE => $invalidDescriptions ],
			),
			UseCaseError::PATCH_RESULT_INVALID_VALUE,
			'Invalid value in patch result',
			[
				UseCaseError::CONTEXT_PATH => '',
				UseCaseError::CONTEXT_VALUE => $invalidDescriptions,
			],
		];

		$language = 'en';
		$description = "tab characters \t not allowed";
		yield 'invalid description' => [
			[ $language => $description ],
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
				],
			),
			UseCaseError::PATCH_RESULT_INVALID_VALUE,
			'Invalid value in patch result',
			[
				UseCaseError::CONTEXT_PATH => "/$language",
				UseCaseError::CONTEXT_VALUE => $description,
			],
		];

		$tooLongDescription = 'This description is too long.';
		yield 'description too long' => [
			[ $language => $tooLongDescription ],
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $tooLongDescription,
					ItemDescriptionValidator::CONTEXT_LIMIT => 250,
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
				]
			),
			UseCaseError::PATCH_RESULT_VALUE_TOO_LONG,
			'Patched value is too long',
			[
				UseCaseError::CONTEXT_PATH => "/$language",
				UseCaseError::CONTEXT_LIMIT => 250,
			],
		];

		$collidingLabel = 'label already exists on an item with the same description';
		$collidingDescription = 'description already exists on an item with the same label';
		$conflictingItemId = 'Q345';
		yield 'label/description collision' => [
			[ $language => $collidingLabel ],
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					ItemDescriptionValidator::CONTEXT_LABEL => $collidingLabel,
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $collidingDescription,
					ItemDescriptionValidator::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				]
			),
			UseCaseError::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
					UseCaseError::CONTEXT_LANGUAGE => $language,
					UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				],
			],
		];
	}

	public function testGivenInvalidDescriptions_throwsInvalidDescriptionsError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), '' );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newPatchResultInvalidValue( '', '' ), $e );
		}
	}

	public function testGivenEmptyDescription_throwsEmptyDescriptionError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), [ 'en' => '' ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newPatchResultInvalidValue( '/en', '' ), $e );
		}
	}

	public function testGivenInvalidDescriptionType_throwsInvalidDescriptionError(): void {
		$invalidDescription = 123;
		try {
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), [ 'en' => $invalidDescription ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newPatchResultInvalidValue( '/en', $invalidDescription ), $e );
		}
	}

	public function testGivenDescriptionSameAsLabelForLanguage_throwsUseCaseError(): void {
		$language = 'en';
		$this->descriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$this->descriptionValidator->method( 'validate' )->willReturn(
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => $language ]
			)
		);
		try {
			$this->newValidator()->validateAndDeserialize(
				new TermList(),
				new TermList(),
				[ $language => 'Description same as label' ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::DATA_POLICY_VIOLATION, $e->getErrorCode() );
			$this->assertSame( 'Edit violates data policy', $e->getErrorMessage() );
			$this->assertEquals( [
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [ UseCaseError::CONTEXT_LANGUAGE => $language ],
			], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseError(): void {
		$invalidLanguage = 'not-a-valid-language-code';
		$this->languageCodeValidator = $this->createStub( DescriptionLanguageCodeValidator::class );
		$this->languageCodeValidator->method( 'validate' )->willReturn(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[ LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => $invalidLanguage ]
			)
		);

		try {
			$this->newValidator()->validateAndDeserialize( new TermList(), new TermList(), [ $invalidLanguage => 'description' ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $e, UseCaseError::newPatchResultInvalidKey( '', $invalidLanguage ) );
		}
	}

	private function newValidator(): PatchedItemDescriptionsValidator {
		return new PatchedItemDescriptionsValidator(
			new DescriptionsSyntaxValidator( new DescriptionsDeserializer(), $this->languageCodeValidator ),
			new ItemDescriptionsContentsValidator( $this->descriptionValidator )
		);
	}

}
