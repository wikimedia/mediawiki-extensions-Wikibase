<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidRequest_doesNothing(): void {
		$validator = new GetItemStatementValidator( new ItemIdValidator() );

		$validator->assertValidRequest( new GetItemStatementRequest(
			'Q777',
			'Q777$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
		) );
	}

	public function testGivenInvalidItemId_throws(): void {
		$validator = new GetItemStatementValidator( new ItemIdValidator() );
		$itemId = 'X777';

		try {
			$validator->assertValidRequest( new GetItemStatementRequest(
				$itemId,
				'Q777$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
			) );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $itemId", $e->getErrorMessage() );
		}
	}

}
