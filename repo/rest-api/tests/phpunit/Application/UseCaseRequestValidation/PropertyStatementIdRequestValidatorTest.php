<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyStatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyStatementIdRequestValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyStatementIdRequestValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyStatementIdRequestValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidRequest_passes(): void {
		$request = $this->createStub( PropertyStatementIdRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P42' );
		$request->method( 'getStatementId' )->willReturn( 'P42$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		( new PropertyStatementIdRequestValidator() )->validateAndDeserialize( $request );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenSameIdInLowercase_passes(): void {
		$request = $this->createStub( PropertyStatementIdRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P42' );
		$request->method( 'getStatementId' )->willReturn( 'p42$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		( new PropertyStatementIdRequestValidator() )->validateAndDeserialize( $request );
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( PropertyStatementIdRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P1' );
		$request->method( 'getStatementId' )->willReturn( 'P11$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		try {
			( new PropertyStatementIdRequestValidator() )->validateAndDeserialize( $request );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::PROPERTY_STATEMENT_ID_MISMATCH, $e->getErrorCode() );
		}
	}

}
