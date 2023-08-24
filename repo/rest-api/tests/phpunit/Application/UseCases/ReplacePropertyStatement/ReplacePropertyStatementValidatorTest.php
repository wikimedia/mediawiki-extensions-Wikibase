<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplacePropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplacePropertyStatementValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidRequest_doesNothing(): void {
		$validator = new ReplacePropertyStatementValidator( new PropertyIdValidator() );
		$validator->assertValidRequest( $this->newUseCaseRequest( 'P123' ) );
	}

	public function testGivenInvalidPropertyId_throwsUseCaseError(): void {
		$validator = new ReplacePropertyStatementValidator( new PropertyIdValidator() );
		$propertyId = 'X123';

		try {
			$validator->assertValidRequest( $this->newUseCaseRequest( $propertyId ) );

			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( $propertyId, $e->getErrorContext()[ UseCaseError::CONTEXT_PROPERTY_ID ] );
		}
	}

	private function newUseCaseRequest( string $propertyId ): ReplacePropertyStatementRequest {
		$useCaseRequest = $this->createStub( ReplacePropertyStatementRequest::class );
		$useCaseRequest->method( 'getPropertyId' )->willReturn( $propertyId );

		return $useCaseRequest;
	}

}
