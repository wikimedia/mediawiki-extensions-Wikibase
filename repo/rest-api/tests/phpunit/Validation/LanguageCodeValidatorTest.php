<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Validation\LanguageCodeValidator
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

	public function testGivenInvalidLanguageCode_returnsValidationError(): void {
		$validLanguageCodes = [ 'ar', 'de', 'en' ];
		$validator = new LanguageCodeValidator( $validLanguageCodes );
		$invalidLanguageCode = 'unknown-language-code';

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

}
