<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\LanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguageValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidIdAndLanguageCode(): void {
		$this->newAliasesInLanguageValidator()
			->assertValidRequest( new GetItemAliasesInLanguageRequest( 'Q321', 'en' ) );
	}

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newAliasesInLanguageValidator()
				->assertValidRequest( new GetItemAliasesInLanguageRequest( $invalidId, 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidId, $useCaseEx->getErrorMessage() );
		}
	}

	public function testWithInvalidLanguageCode(): void {
		$invalidLanguageCode = '1e';

		try {
			$this->newAliasesInLanguageValidator()
				->assertValidRequest( new GetItemAliasesInLanguageRequest( 'Q123', $invalidLanguageCode ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid language code: ' . $invalidLanguageCode, $useCaseEx->getErrorMessage() );
		}
	}

	private function newAliasesInLanguageValidator(): GetItemAliasesInLanguageValidator {
		return ( new GetItemAliasesInLanguageValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
		) );
	}

}
