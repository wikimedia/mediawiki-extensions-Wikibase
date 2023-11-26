<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyDescriptions;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoPropertyDescriptionValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedPropertyDescriptionsValidatorTest extends TestCase {

	private	const LIMIT = 50;
	private const EN_LABEL = 'en-label';

	/**
	 * @dataProvider validDescriptionsProvider
	 */
	public function testWithValidDescriptions( array $descriptionsSerialization, TermList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator()
				->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), $descriptionsSerialization )
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

	/**
	 * @dataProvider invalidDescriptionsProvider
	 */
	public function testWithInvalidDescriptions( array $serialization, UseCaseError $expectedError ): void {
		try {
			$this->newValidator()->validateAndDeserialize( new NumericPropertyId( 'P123' ), new TermList(), $serialization );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedError, $error );
		}
	}

	public static function invalidDescriptionsProvider(): Generator {
		yield 'invalid language' => [
			[ 'bad-language-code' => 'description text' ],
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
				"Not a valid language code 'bad-language-code' in changed descriptions",
				[ UseCaseError::CONTEXT_LANGUAGE => 'bad-language-code' ]
			),
		];

		yield 'empty description' => [
			[ 'en' => '' ],
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_EMPTY,
				"Changed description for 'en' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		$invalidDescriptionText = "tab\t tab";
		yield 'invalid description text' => [
			[ 'en' => $invalidDescriptionText ],
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID,
				"Changed description for 'en' is invalid: $invalidDescriptionText",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $invalidDescriptionText,
				]
			),
		];

		$tooLongDescription = str_repeat( 'A', self::LIMIT + 1 );
		yield 'description too long' => [
			[ 'en' => $tooLongDescription ],
			new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,
				"Changed description for 'en' must not be more than " . self::LIMIT . ' characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $tooLongDescription,
					UseCaseError::CONTEXT_CHARACTER_LIMIT => self::LIMIT,
				]
			),
		];

		yield 'description equals label' => [
			[ 'en' => self::EN_LABEL ],
			new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
				'Label and description for language code en can not have the same value.',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
	}

	private function newValidator(): PatchedPropertyDescriptionsValidator {
		$validLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];

		$propertyRetriever = $this->createStub( PropertyRetriever::class );
		$propertyRetriever->method( 'getProperty' )
			->willReturn( new Property(
				null,
				new Fingerprint( new TermList( [ new Term( 'en', self::EN_LABEL ) ] ) ),
				'string'
			) );

		return new PatchedPropertyDescriptionsValidator(
			new DescriptionsDeserializer(),
			new WikibaseRepoPropertyDescriptionValidator(
				new TermValidatorFactory(
					self::LIMIT,
					$validLanguageCodes,
					$this->createStub( EntityIdParser::class ),
					$this->createStub( TermsCollisionDetectorFactory::class ),
					$this->createStub( TermLookup::class ),
					$this->createStub( LanguageNameUtils::class )
				),
				$propertyRetriever
			),
			new LanguageCodeValidator( $validLanguageCodes )
		);
	}

}
