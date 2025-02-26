<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageCodeValidatorTest extends TestCase {

	public function testValidate_passes(): void {
		$this->assertNull(
			$this->newLanguageCodeValidator()->validate( 'en' )
		);
	}

	public function testInvalidLanguageCode_returnsError(): void {
		$invalidLanguageCode = 'xyz';
		$validationError = $this->newLanguageCodeValidator()->validate( $invalidLanguageCode );

		$this->assertNotNull( $validationError );
		$this->assertEquals(
			LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
			$validationError->getCode()
		);
		$this->assertEquals(
			[ LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => $invalidLanguageCode ],
			$validationError->getContext()
		);
	}

	private function newLanguageCodeValidator(): LanguageCodeValidator {
		return new LanguageCodeValidator(
			new CompositeValidator( [
				new TypeValidator( 'string' ),
				new MembershipValidator( WikibaseRepo::getTermsLanguages()->getLanguages(), 'not-a-language' ),
				new NotMulValidator( MediaWikiServices::getInstance()->getLanguageNameUtils() ),
			] )
		);
	}
}
