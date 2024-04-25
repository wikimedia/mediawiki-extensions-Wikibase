<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageCodeValidatorTest extends TestCase {

	public function testGivenValidLanguageCode_returnsNull(): void {
		$validLanguageCodes = [ 'ar', 'de', 'en' ];
		$validator = new LanguageCodeValidator( $validLanguageCodes );

		$this->assertNull( $validator->validate( 'de' ) );
	}

	/**
	 * @dataProvider provideInvalidLanguageCode
	 */
	public function testGivenInvalidLanguageCode_returnsValidationError( string $invalidLanguageCode ): void {
		$validLanguageCodes = [ 'ar', 'de', 'en' ];
		$validator = new LanguageCodeValidator( $validLanguageCodes );

		$error = $validator->validate( $invalidLanguageCode );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame(
			LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
			$error->getCode()
		);
		$this->assertSame(
			$invalidLanguageCode,
			$error->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE]
		);
	}

	public function provideInvalidLanguageCode(): Generator {
		yield 'fr not in list of valid language codes' => [ 'fr' ];
		yield 'empty string not in list of valid language codes' => [ '' ];
		yield "'123' not in list of valid language codes" => [ '123' ];
	}

}
