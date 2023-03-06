<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemAliases\GetItemAliasesRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemAliases\GetItemAliasesValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newAliasesValidator()
				->assertValidRequest( new GetItemAliasesRequest( $invalidId ) );
		} catch ( UseCaseException $useCaseEx ) {
			$this->assertSame( ItemIdValidator::CODE_INVALID, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidId, $useCaseEx->getErrorMessage() );
		}
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidId(): void {
		$this->newAliasesValidator()
			->assertValidRequest( new GetItemAliasesRequest( 'Q321' ) );
	}

	private function newAliasesValidator(): GetItemAliasesValidator {
		return ( new GetItemAliasesValidator( new ItemIdValidator() ) );
	}

}
