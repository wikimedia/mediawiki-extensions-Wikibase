<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionsValidator
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
		} catch ( UseCaseException $useCaseEx ) {
			$this->assertSame( ItemIdValidator::CODE_INVALID, $useCaseEx->getErrorCode() );
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
