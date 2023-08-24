<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest(): void {
		$request = $this->createStub( PatchItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		( new PatchItemStatementValidator( new ItemIdValidator() ) )->assertValidRequest( $request );
	}

	public function testValidate_withInvalidRequest(): void {
		$invalidItemId = 'X123';
		$request = $this->createStub( PatchItemStatementRequest::class );
		$request->method( 'getItemId' )->willReturn( $invalidItemId );

		$validator = new PatchItemStatementValidator( new ItemIdValidator() );
		try {
			$validator->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
		}
	}
}
