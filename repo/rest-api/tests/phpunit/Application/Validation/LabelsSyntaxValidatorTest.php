<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsSyntaxValidatorTest extends TestCase {

	private const VALID_LANGUAGES = [ 'ar', 'de', 'en', 'ko' ];

	/**
	 * @dataProvider validLabelsProvider
	 */
	public function testValid( array $serialization, PartiallyValidatedLabels $expectedResult ): void {
		$validator = $this->newValidator();
		$this->assertNull( $validator->validate( $serialization ) );
		$this->assertEquals( $expectedResult, $validator->getPartiallyValidatedLabels() );
	}

	public static function validLabelsProvider(): Generator {
		yield 'empty' => [ [], new PartiallyValidatedLabels() ];
		yield 'some labels' => [
			[ 'de' => 'Kartoffel', 'ko' => '감자' ],
			new PartiallyValidatedLabels( [
				new Term( 'de', 'Kartoffel' ),
				new Term( 'ko', '감자' ),
			] ),
		];
	}

	/**
	 * @dataProvider invalidLabelsProvider
	 */
	public function testInvalid( array $serialization, ValidationError $expectedError ): void {
		$this->assertEquals(
			$expectedError,
			$this->newValidator()->validate( $serialization )
		);
	}

	public static function invalidLabelsProvider(): Generator {
		$invalidLabels = [ 'some label', 'some other label' ];
		yield 'invalid labels - sequential array' => [
			$invalidLabels,
			new ValidationError(
				LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE,
				[ LabelsSyntaxValidator::CONTEXT_VALUE => $invalidLabels ]
			),
		];

		yield 'invalid language code - integer' => [
			[ 9729 => 'some label' ],
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => '9729',
					LanguageCodeValidator::CONTEXT_FIELD => 'labels',
				]
			),
		];

		yield 'invalid language code - not in the allowed list' => [
			[ 'invalid-language' => 'some label' ],
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'invalid-language',
					LanguageCodeValidator::CONTEXT_FIELD => 'labels',
				]
			),
		];

		yield 'invalid label - integer' => [
			[ 'de' => 6729 ],
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'de',
					LabelsSyntaxValidator::CONTEXT_LABEL => 6729,
				]
			),
		];

		yield 'invalid label - zero length string' => [
			[ 'de' => '' ],
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'de' ]
			),
		];

		yield 'invalid label - whitespace only' => [
			[ 'de' => " \t " ],
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_LANGUAGE => 'de' ]
			),
		];
	}

	private function newValidator(): LabelsSyntaxValidator {
		return new LabelsSyntaxValidator(
			new LabelsDeserializer(),
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( self::VALID_LANGUAGES ) )
		);
	}

}
