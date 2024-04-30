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
		yield 'invalid language code' => [
			[ 'invalid-language' => 'some label' ],
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'invalid-language',
					LanguageCodeValidator::CONTEXT_PATH => 'labels',
				]
			),
		];
		yield 'labels not associative' => [
			[ 'some label', 'some other label' ],
			new ValidationError( LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE ),
		];
		yield 'label empty' => [
			[ 'de' => '' ],
			new ValidationError(
				LabelsSyntaxValidator::CODE_EMPTY_LABEL,
				[ LabelsSyntaxValidator::CONTEXT_FIELD_LANGUAGE => 'de' ]
			),
		];
		yield 'invalid label type' => [
			[ 'de' => 7 ],
			new ValidationError(
				LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE,
				[
					LabelsSyntaxValidator::CONTEXT_FIELD_LANGUAGE => 'de',
					LabelsSyntaxValidator::CONTEXT_FIELD_LABEL => 7,
				]
			),
		];
	}

	private function newValidator(): LabelsSyntaxValidator {
		return new LabelsSyntaxValidator(
			new LabelsDeserializer(),
			new LanguageCodeValidator( self::VALID_LANGUAGES )
		);
	}

}
