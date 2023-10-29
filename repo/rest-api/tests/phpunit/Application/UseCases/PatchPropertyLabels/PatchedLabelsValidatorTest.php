<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyLabels;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchedLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchedLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidatorTest extends TestCase {

	private PropertyLabelValidator $labelValidator;
	private LanguageCodeValidator $languageCodeValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->labelValidator = $this->createStub( PropertyLabelValidator::class );
		$this->languageCodeValidator = $this->createStub( LanguageCodeValidator::class );
	}

	/**
	 * @dataProvider validLabelsProvider
	 */
	public function testWithValidLabels( array $labelsSerialization, TermList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator()->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), $labelsSerialization )
		);
	}

	public static function validLabelsProvider(): Generator {
		yield 'no labels' => [
			[],
			new TermList(),
		];
		yield 'valid labels' => [
			[ 'en' => 'instance of', 'de' => 'ist ein(e)' ],
			new TermList( [ new Term( 'en', 'instance of' ), new Term( 'de', 'ist ein(e)' ) ] ),
		];
	}

	public function testValidateOnlyModifiedLabels(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$originalLabels = new TermList( [
			new Term( 'en', 'type' ),
			new Term( 'de', 'ist ein(e)' ),
		] );

		// only 'en' and 'bar' labels have been patched
		$patchedLabels = [ 'en' => 'instance of', 'de' => 'ist ein(e)', 'bar' => 'is a' ];

		// expect validation only for the modified labels
		$this->labelValidator = $this->createMock( PropertyLabelValidator::class );
		$this->labelValidator->expects( $this->exactly( 2 ) )
			->method( 'validate' )
			->withConsecutive(
				[ $propertyId, 'en', 'instance of' ],
				[ $propertyId, 'bar', 'is a' ],
			);

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'instance of' ),
				new Term( 'de', 'ist ein(e)' ),
				new Term( 'bar', 'is a' ),
			] ),
			$this->newValidator()->validateAndDeserialize( $propertyId, $originalLabels, $patchedLabels )
		);
	}

	/**
	 * @dataProvider invalidLabelsProvider
	 */
	public function testWithInvalidLabels(
		array $labelsSerialization,
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = null
	): void {
		$this->labelValidator = $this->createStub( PropertyLabelValidator::class );
		$this->labelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), $labelsSerialization );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertEquals( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidLabelsProvider(): Generator {
		$language = 'en';
		$label = "tab characters \t not allowed";
		yield 'invalid label' => [
			[ $language => $label ],
			new ValidationError(
				PropertyLabelValidator::CODE_INVALID,
				[ PropertyLabelValidator::CONTEXT_LABEL => $label ],
			),
			UseCaseError::PATCHED_LABEL_INVALID,
			"Changed label for '$language' is invalid: {$label}",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_VALUE => $label,
			],
		];

		$tooLongLabel = 'This label is too long.';
		yield 'label too long' => [
			[ $language => $tooLongLabel ],
			new ValidationError(
				PropertyLabelValidator::CODE_TOO_LONG,
				[
					PropertyLabelValidator::CONTEXT_LABEL => $tooLongLabel,
					PropertyLabelValidator::CONTEXT_LIMIT => 250,
					PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
				]
			),
			UseCaseError::PATCHED_LABEL_TOO_LONG,
			"Changed label for '$language' must not be more than 250 characters long",
			[
				UseCaseError::CONTEXT_VALUE => $tooLongLabel,
				UseCaseError::CONTEXT_CHARACTER_LIMIT => 250,
				UseCaseError::CONTEXT_LANGUAGE => $language,
			],
		];

		$language = 'en';
		$label = 'My Label';
		$propertyId = 'P456';
		yield 'label not unique' => [
			[ $language => $label ],
			new ValidationError( PropertyLabelValidator::CODE_LABEL_DUPLICATE, [
				PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID => $propertyId,
			] ),
			UseCaseError::PATCHED_PROPERTY_LABEL_DUPLICATE,
			"Property $propertyId already has label '$label' associated with language code '$language'",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $label,
				UseCaseError::CONTEXT_MATCHING_PROPERTY_ID => $propertyId,
			],
		];
	}

	public function testGivenEmptyLabel_throwsEmptyLabelError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), [ 'en' => '' ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_LABEL_EMPTY, $e->getErrorCode() );
			$this->assertSame( "Changed label for 'en' cannot be empty", $e->getErrorMessage() );
			$this->assertEquals( [ UseCaseError::CONTEXT_LANGUAGE => 'en' ], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLabelType_throwsInvalidLabelError(): void {
		$invalidLabel = 123;
		try {
			$this->newValidator()->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), [ 'en' => $invalidLabel ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_LABEL_INVALID, $e->getErrorCode() );
			$this->assertStringContainsString( 'en', $e->getErrorMessage() );
			$this->assertStringContainsString( "$invalidLabel", $e->getErrorMessage() );
			$this->assertEquals(
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => "$invalidLabel" ],
				$e->getErrorContext()
			);
		}
	}

	public function testGivenLabelSameAsDescriptionForLanguage_throwsUseCaseError(): void {
		$language = 'en';
		$this->labelValidator = $this->createStub( PropertyLabelValidator::class );
		$this->labelValidator->method( 'validate' )->willReturn(
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => $language ]
			)
		);
		try {
			$this->newValidator()->validateAndDeserialize(
				new NumericPropertyId( 'P345' ),
				new TermList(),
				[ $language => 'Label same as description.' ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE, $e->getErrorCode() );
			$this->assertSame( "Label and description for language code {$language} can not have the same value.", $e->getErrorMessage() );
			$this->assertEquals( [ UseCaseError::CONTEXT_LANGUAGE => $language ], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseError(): void {
		$invalidLanguage = 'not-a-valid-language-code';
		$this->languageCodeValidator = $this->createStub( LanguageCodeValidator::class );
		$this->languageCodeValidator->method( 'validate' )->willReturn(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[ LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => $invalidLanguage ]
			)
		);

		try {
			$this->newValidator()->validateAndDeserialize(
				new NumericPropertyId( 'P123' ),
				new TermList(),
				[ $invalidLanguage => 'potato' ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE, $e->getErrorCode() );
			$this->assertSame( "Not a valid language code '$invalidLanguage' in changed labels", $e->getErrorMessage() );
			$this->assertEquals( [ UseCaseError::CONTEXT_LANGUAGE => $invalidLanguage ], $e->getErrorContext() );
		}
	}

	private function newValidator(): PatchedLabelsValidator {
		return new PatchedLabelsValidator(
			new LabelsDeserializer(),
			$this->labelValidator,
			$this->languageCodeValidator
		);
	}

}
