<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoDescriptionLanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoDescriptionLanguageCodeValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoDescriptionLanguageCodeValidatorTest extends TestCase {

	/**
	 * @dataProvider provideValidDescriptionLanguageCode
	 */
	public function testGivenValidLanguageCode_returnsNull( string $language ): void {
		$this->assertNull( $this->newValidator()->validate( $language ) );
	}

	public function provideValidDescriptionLanguageCode(): Generator {
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
			DescriptionLanguageCodeValidator::CODE_INVALID_LANGUAGE,
			[ DescriptionLanguageCodeValidator::CONTEXT_LANGUAGE => $language ]
		);
		$validationError = $this->newValidator()->validate( $language );
		$this->assertEquals( $expectedValidationError, $validationError );
	}

	public function provideInvalidDescriptionLanguageCode(): Generator {
		yield 'xyz not a language code' => [ 'xyz' ];
		yield 'en-uk not a language code' => [ 'en-uk' ];
		yield "mul can't be used as a description language code" => [ 'mul' ];
	}

	private function newValidator(): WikibaseRepoDescriptionLanguageCodeValidator {
		return new WikibaseRepoDescriptionLanguageCodeValidator(
			WikibaseRepo::getTermValidatorFactory()
		);
	}

}
