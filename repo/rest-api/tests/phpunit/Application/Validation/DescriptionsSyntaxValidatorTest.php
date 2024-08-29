<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsSyntaxValidatorTest extends TestCase {

	private const VALID_LANGUAGES = [ 'ar', 'de', 'en', 'ko' ];

	/**
	 * @dataProvider validDescriptionsProvider
	 */
	public function testValid( array $serialization, PartiallyValidatedDescriptions $expectedResult ): void {
		$validator = $this->newValidator();
		$this->assertNull( $validator->validate( $serialization ) );
		$this->assertEquals( $expectedResult, $validator->getPartiallyValidatedDescriptions() );
	}

	public static function validDescriptionsProvider(): Generator {
		yield 'empty' => [ [], new PartiallyValidatedDescriptions() ];
		yield 'some descriptions' => [
			[ 'en' => 'some description', 'de' => 'some other description' ],
			new PartiallyValidatedDescriptions( [
				new Term( 'en', 'some description' ),
				new Term( 'de', 'some other description' ),
			] ),
		];
	}

	/**
	 * @dataProvider invalidDescriptionsProvider
	 */
	public function testInvalid( array $serialization, ValidationError $expectedError ): void {
		$this->assertEquals(
			$expectedError,
			$this->newValidator()->validate( $serialization )
		);
	}

	public static function invalidDescriptionsProvider(): Generator {
		$invalidDescriptions = [ 'some description', 'some other description' ];
		yield 'invalid descriptions - sequential array' => [
			$invalidDescriptions,
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE,
				[ DescriptionsSyntaxValidator::CONTEXT_VALUE => $invalidDescriptions ]
			),
		];

		yield 'invalid language code - integer' => [
			[ 4290 => 'some description' ],
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => '4290',
					LanguageCodeValidator::CONTEXT_FIELD => 'descriptions',
				]
			),
		];

		yield 'invalid language code - not in the allowed list' => [
			[ 'invalid-language' => 'some description' ],
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'invalid-language',
					LanguageCodeValidator::CONTEXT_FIELD => 'descriptions',
				]
			),
		];

		yield 'invalid description - integer' => [
			[ 'de' => 2421 ],
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE,
				[
					DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'de',
					DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION => 2421,
				]
			),
		];

		yield 'invalid description - zero length string' => [
			[ 'de' => '' ],
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'de' ]
			),
		];

		yield 'invalid description - whitespace only' => [
			[ 'de' => " \t " ],
			new ValidationError(
				DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION,
				[ DescriptionsSyntaxValidator::CONTEXT_LANGUAGE => 'de' ]
			),
		];
	}

	private function newValidator(): DescriptionsSyntaxValidator {
		return new DescriptionsSyntaxValidator(
			new DescriptionsDeserializer(),
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( self::VALID_LANGUAGES ) )
		);
	}

}
