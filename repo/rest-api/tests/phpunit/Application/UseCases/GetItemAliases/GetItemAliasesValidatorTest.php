<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newAliasesValidator()->assertValidRequest( new GetItemAliasesRequest( $invalidId ) );
			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidId", $useCaseEx->getErrorMessage() );
		}
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidId(): void {
		$this->newAliasesValidator()->assertValidRequest( new GetItemAliasesRequest( 'Q321' ) );
	}

	private function newAliasesValidator(): GetItemAliasesValidator {
		return ( new GetItemAliasesValidator( new ItemIdValidator() ) );
	}

}
