<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidIdAndLanguageCode(): void {
		$this->newValidator()->assertValidRequest( new GetItemLabelRequest( 'Q321', 'en' ) );
	}

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newValidator()->assertValidRequest( new GetItemLabelRequest( $invalidId, 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $error->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidId", $error->getErrorMessage() );
		}
	}

	public function testWithInvalidLanguageCode(): void {
		$invalidLanguage = '1e';

		try {
			$this->newValidator()->assertValidRequest( new GetItemLabelRequest( 'Q123', $invalidLanguage ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $error->getErrorCode() );
			$this->assertSame( "Not a valid language code: $invalidLanguage", $error->getErrorMessage() );
		}
	}

	private function newValidator(): GetItemLabelValidator {
		return ( new GetItemLabelValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
		) );
	}

}
