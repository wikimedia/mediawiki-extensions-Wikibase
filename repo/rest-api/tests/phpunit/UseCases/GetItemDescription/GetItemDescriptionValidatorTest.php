<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemDescription\GetItemDescriptionRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemDescription\GetItemDescriptionValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\LanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidIdAndLanguageCode(): void {
		$this->newDescriptionValidator()
			->assertValidRequest( new GetItemDescriptionRequest( 'Q321', 'en' ) );
	}

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newDescriptionValidator()
				->assertValidRequest( new GetItemDescriptionRequest( $invalidId, 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidId, $useCaseEx->getErrorMessage() );
		}
	}

	public function testWithInvalidLanguageCode(): void {
		$invalidLanguageCode = '1e';

		try {
			$this->newDescriptionValidator()
				->assertValidRequest( new GetItemDescriptionRequest( 'Q123', $invalidLanguageCode ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid language code: ' . $invalidLanguageCode, $useCaseEx->getErrorMessage() );
		}
	}

	private function newDescriptionValidator(): GetItemDescriptionValidator {
		return ( new GetItemDescriptionValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
		) );
	}

}
