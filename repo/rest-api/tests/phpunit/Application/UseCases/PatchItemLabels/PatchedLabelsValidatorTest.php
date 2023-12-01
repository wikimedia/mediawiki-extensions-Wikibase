<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemLabels;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchedLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchedLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidatorTest extends TestCase {

	private ItemLabelValidator $labelValidator;
	private LanguageCodeValidator $languageCodeValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->labelValidator = $this->createStub( ItemLabelValidator::class );
		$this->languageCodeValidator = $this->createStub( LanguageCodeValidator::class );
	}

	/**
	 * @dataProvider validLabelsProvider
	 */
	public function testWithValidLabels( array $labelsSerialization, TermList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator()->validateAndDeserialize( new ItemId( 'Q123' ), new TermList(), $labelsSerialization )
		);
	}

	public static function validLabelsProvider(): Generator {
		yield 'no labels' => [
			[],
			new TermList(),
		];
		yield 'valid labels' => [
			[ 'en' => 'potato', 'de' => 'Kartoffel' ],
			new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
		];
	}

	public function testValidateOnlyModifiedLabels(): void {
		$itemId = new ItemId( 'Q123' );
		$originalLabels = new TermList( [
			new Term( 'en', 'spud' ),
			new Term( 'de', 'Kartoffel' ),
		] );

		// only 'en' and 'bar' labels have been patched
		$patchedLabels = [ 'en' => 'potato', 'de' => 'Kartoffel', 'bar' => 'Erdapfel' ];

		// expect validation only for the modified labels
		$this->labelValidator = $this->createMock( ItemLabelValidator::class );
		$expectedArgs = [
			[ $itemId, 'en', 'potato' ],
			[ $itemId, 'bar', 'Erdapfel' ],
		];
		$this->labelValidator->expects( $this->exactly( 2 ) )
			->method( 'validate' )
			->willReturnCallback( function ( $itemId, $language, $label ) use ( &$expectedArgs ) {
				$curExpectedArgs = array_shift( $expectedArgs );
				$this->assertSame( $curExpectedArgs[0], $itemId );
				$this->assertSame( $curExpectedArgs[1], $language );
				$this->assertSame( $curExpectedArgs[2], $label );
				return null;
			} );

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'potato' ),
				new Term( 'de', 'Kartoffel' ),
				new Term( 'bar', 'Erdapfel' ),
			] ),
			$this->newValidator()->validateAndDeserialize( $itemId, $originalLabels, $patchedLabels )
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
		$this->labelValidator = $this->createStub( ItemLabelValidator::class );
		$this->labelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->validateAndDeserialize( new ItemId( 'Q123' ), new TermList(), $labelsSerialization );

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
				ItemLabelValidator::CODE_INVALID,
				[ ItemLabelValidator::CONTEXT_LABEL => $label ],
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
				ItemLabelValidator::CODE_TOO_LONG,
				[
					ItemLabelValidator::CONTEXT_LABEL => $tooLongLabel,
					ItemLabelValidator::CONTEXT_LIMIT => 250,
					ItemLabelValidator::CONTEXT_LANGUAGE => $language,
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

		$collidingLabel = 'This label already exists on an item with the same description.';
		$collidingDescription = 'This discription already exists on an item with the same label.';
		$collidingItemId = 'Q345';
		yield 'label/description collision' => [
			[ $language => $collidingLabel ],
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => $language,
					ItemLabelValidator::CONTEXT_LABEL => $collidingLabel,
					ItemLabelValidator::CONTEXT_DESCRIPTION => $collidingDescription,
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => $collidingItemId,
				]
			),
			UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
			"Item $collidingItemId already has label '$collidingLabel' associated with language code $language, " .
			'using the same description text.',
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $collidingLabel,
				UseCaseError::CONTEXT_DESCRIPTION => $collidingDescription,
				UseCaseError::CONTEXT_MATCHING_ITEM_ID => $collidingItemId,
			],
		];
	}

	public function testGivenEmptyLabel_throwsEmptyLabelError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( new ItemId( 'Q123' ), new TermList(), [ 'en' => '' ] );
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
			$this->newValidator()->validateAndDeserialize( new ItemId( 'Q123' ), new TermList(), [ 'en' => $invalidLabel ] );
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
		$this->labelValidator = $this->createStub( ItemLabelValidator::class );
		$this->labelValidator->method( 'validate' )->willReturn(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			)
		);
		try {
			$this->newValidator()->validateAndDeserialize(
				new ItemId( 'Q345' ),
				new TermList(),
				[ $language => 'Label same as description.' ]
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE, $e->getErrorCode() );
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
			$this->newValidator()->validateAndDeserialize( new ItemId( 'Q123' ), new TermList(), [ $invalidLanguage => 'potato' ] );
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
