<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest(): void {
		$request = $this->createStub( ReplaceItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		( new ReplaceItemStatementValidator( new ItemIdValidator() ) )->assertValidRequest( $request );
	}

	public function testValidate_withInvalidRequest(): void {
		$invalidItemId = 'X123';
		$request = $this->createStub( ReplaceItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( $invalidItemId );

		$validator = new ReplaceItemStatementValidator( new ItemIdValidator() );
		try {
			$validator->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidItemId", $e->getErrorMessage() );
		}
	}
}
