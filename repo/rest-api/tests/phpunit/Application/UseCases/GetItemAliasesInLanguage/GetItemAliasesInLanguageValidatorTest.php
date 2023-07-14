<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator
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
		$this->newValidator()->assertValidRequest( new GetItemAliasesInLanguageRequest( 'Q321', 'en' ) );
	}

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newValidator()->assertValidRequest( new GetItemAliasesInLanguageRequest( $invalidId, 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidId", $useCaseEx->getErrorMessage() );
		}
	}

	public function testWithInvalidLanguageCode(): void {
		$invalidLanguage = '1e';

		try {
			$this->newValidator()->assertValidRequest( new GetItemAliasesInLanguageRequest( 'Q123', $invalidLanguage ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid language code: $invalidLanguage", $useCaseEx->getErrorMessage() );
		}
	}

	private function newValidator(): GetItemAliasesInLanguageValidator {
		return ( new GetItemAliasesInLanguageValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
		) );
	}

}
