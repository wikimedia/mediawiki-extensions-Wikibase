<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemStatements;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		try {
			$this->newStatementsValidator()->assertValidRequest( new GetItemStatementsRequest( 'X123' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X123', $e->getErrorMessage() );
		}
	}

	/**
	 * @dataProvider provideInvalidPropertyId
	 */
	public function testWithInvalidPropertyFilter( string $invalidPropertyId ): void {
		try {
			$this->newStatementsValidator()->assertValidRequest( new GetItemStatementsRequest( 'Q123', $invalidPropertyId ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid property ID: $invalidPropertyId", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PROPERTY_ID => $invalidPropertyId ], $e->getErrorContext() );
		}
	}

	public function provideInvalidPropertyId(): Generator {
		yield 'invalid truthy id' => [ 'X123' ];
		yield 'invalid falsy id' => [ '0' ];
	}

	/**
	 * @dataProvider validRequestProvider
	 * @doesNotPerformAssertions
	 */
	public function testWithValidRequest( GetItemStatementsRequest $request ): void {
		$this->newStatementsValidator()->assertValidRequest( $request );
	}

	public static function validRequestProvider(): Generator {
		yield [ new GetItemStatementsRequest( 'Q321' ) ];
		yield [ new GetItemStatementsRequest( 'Q321', 'P123' ) ];
	}

	private function newStatementsValidator(): GetItemStatementsValidator {
		return ( new GetItemStatementsValidator( new ItemIdValidator(), new PropertyIdValidator() ) );
	}

}
