<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newDescriptionsValidator()
				->assertValidRequest( new GetItemDescriptionsRequest( $invalidId ) );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidId, $useCaseEx->getErrorMessage() );
		}
	}

	public function testWithValidId(): void {
		$this->expectNotToPerformAssertions();
		$this->newDescriptionsValidator()
			->assertValidRequest( new GetItemDescriptionsRequest( 'Q321' ) );
	}

	private function newDescriptionsValidator(): GetItemDescriptionsValidator {
		return ( new GetItemDescriptionsValidator( new ItemIdValidator() ) );
	}

}
