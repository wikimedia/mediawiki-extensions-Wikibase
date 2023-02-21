<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		try {
			$this->newLabelsValidator()
				->assertValidRequest( new GetItemLabelsRequest( $invalidId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $useCaseEx ) {
			$this->assertSame( ItemIdValidator::CODE_INVALID, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidId, $useCaseEx->getErrorMessage() );
		}
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidId(): void {
		$this->newLabelsValidator()
			->assertValidRequest( new GetItemLabelsRequest( 'Q321' ) );
	}

	private function newLabelsValidator(): GetItemLabelsValidator {
		return ( new GetItemLabelsValidator( new ItemIdValidator() ) );
	}

}
