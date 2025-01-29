<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequestValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequestValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemStatementIdRequestValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidRequest_passes(): void {
		$request = $this->createStub( ItemStatementIdRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q42' );
		$request->method( 'getStatementId' )->willReturn( 'Q42$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		( new ItemStatementIdRequestValidator() )->validateAndDeserialize( $request );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenSameIdInLowercase_passes(): void {
		$request = $this->createStub( ItemStatementIdRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q42' );
		$request->method( 'getStatementId' )->willReturn( 'q42$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		( new ItemStatementIdRequestValidator() )->validateAndDeserialize( $request );
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( ItemStatementIdRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q1' );
		$request->method( 'getStatementId' )->willReturn( 'Q11$F078E5B3-F9A8-480E-B7AC-D97778CBBEF9' );

		try {
			( new ItemStatementIdRequestValidator() )->validateAndDeserialize( $request );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::ITEM_STATEMENT_ID_MISMATCH, $e->getErrorCode() );
		}
	}

}
