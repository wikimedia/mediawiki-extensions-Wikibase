<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValueValidatorLanguageCodeValidatorTest extends TestCase {

	private const ALLOWED_LANGUAGES = [ 'ar', 'de', 'en', 'en-gb' ];

	/**
	 * @dataProvider provideValidDescriptionLanguageCode
	 */
	public function testGivenValidLanguageCode_returnsNull( string $language ): void {
		$this->assertNull( $this->newValidator()->validate( $language ) );
	}

	public static function provideValidDescriptionLanguageCode(): Generator {
		yield 'English' => [ 'en' ];
		yield 'German' => [ 'de' ];
		yield 'Arabic' => [ 'ar' ];
		yield 'British English' => [ 'en-gb' ];
	}

	/**
	 * @dataProvider provideInvalidDescriptionLanguageCode
	 */
	public function testGivenInvalidLanguageCode_returnsValidationError( string $language ): void {
		$expectedValidationError = new ValidationError(
			LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
			[ LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => $language ]
		);
		$validationError = $this->newValidator()->validate( $language );
		$this->assertEquals( $expectedValidationError, $validationError );
	}

	public static function provideInvalidDescriptionLanguageCode(): Generator {
		yield 'xyz not a language code' => [ 'xyz' ];
		yield 'en-uk not a language code' => [ 'en-uk' ];
	}

	private function newValidator(): ValueValidatorLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator( new MembershipValidator( self::ALLOWED_LANGUAGES ) );
	}

}
