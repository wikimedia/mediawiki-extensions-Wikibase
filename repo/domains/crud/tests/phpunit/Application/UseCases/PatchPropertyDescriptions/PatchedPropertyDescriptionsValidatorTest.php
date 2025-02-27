<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\PatchPropertyDescriptions;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryPropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator
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
				->validateAndDeserialize( new TermList(), new TermList(), $descriptionsSerialization )
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
	 *
	 * @param mixed $serialization
	 * @param UseCaseError $expectedError
	 */
	public function testWithInvalidDescriptions( $serialization, UseCaseError $expectedError ): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				new TermList(),
				new TermList( [ new Term( 'en', self::EN_LABEL ) ] ),
				$serialization
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedError, $error );
		}
	}

	public static function invalidDescriptionsProvider(): Generator {
		yield 'invalid language' => [
			[ 'bad-language-code' => 'description text' ],
			UseCaseError::newPatchResultInvalidKey( '', 'bad-language-code' ),
		];

		yield 'invalid descriptions - string' => [
			'',
			UseCaseError::newPatchResultInvalidValue( '', '' ),
		];

		$invalidDescriptions = [ 'not', 'an', 'associative', 'array' ];
		yield 'invalid descriptions - sequential array' => [
			$invalidDescriptions,
			UseCaseError::newPatchResultInvalidValue( '', $invalidDescriptions ),
		];

		yield 'empty description' => [
			[ 'en' => '' ],
			UseCaseError::newPatchResultInvalidValue( '/en', '' ),
		];

		$invalidDescriptionText = "tab\t tab";
		yield 'invalid description text' => [
			[ 'en' => $invalidDescriptionText ],
			UseCaseError::newPatchResultInvalidValue( '/en', $invalidDescriptionText ),
		];

		$language = 'en';
		yield 'description too long' => [
			[ $language => str_repeat( 'A', self::LIMIT + 1 ) ],
			UseCaseError::newValueTooLong( "/$language", self::LIMIT, true ),
		];

		yield 'description equals label' => [
			[ 'en' => self::EN_LABEL ],
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
	}

	private function newValidator(): PatchedPropertyDescriptionsValidator {
		$validLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];

		return new PatchedPropertyDescriptionsValidator(
			new DescriptionsSyntaxValidator(
				new DescriptionsDeserializer(),
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( $validLanguageCodes ) )
			),
			new PropertyDescriptionsContentsValidator( new TermValidatorFactoryPropertyDescriptionValidator(
				new TermValidatorFactory(
					self::LIMIT,
					$validLanguageCodes,
					$this->createStub( EntityIdParser::class ),
					$this->createStub( TermsCollisionDetectorFactory::class ),
					$this->createStub( TermLookup::class ),
					$this->createStub( LanguageNameUtils::class )
				)
			) )
		);
	}

}
