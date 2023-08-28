<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyStatementValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest(): void {
		$request = $this->createStub( PatchPropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		( new PatchPropertyStatementValidator( new PropertyIdValidator() ) )->assertValidRequest( $request );
	}

	public function testValidate_withInvalidRequest(): void {
		$invalidPropertyId = 'X123';
		$request = $this->createStub( PatchPropertyStatementRequest::class );
		$request->method( 'getPropertyId' )->willReturn( $invalidPropertyId );

		$validator = new PatchPropertyStatementValidator( new PropertyIdValidator() );
		try {
			$validator->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
		}
	}
}
