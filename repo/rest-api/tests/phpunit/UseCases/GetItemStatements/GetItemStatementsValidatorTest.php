<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatements;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator
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
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X123', $e->getErrorMessage() );
		}
	}

	public function testWithInvalidPropertyFilter(): void {
		try {
			$this->newStatementsValidator()->assertValidRequest( new GetItemStatementsRequest( 'Q123', 'X123' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X123', $e->getErrorMessage() );
			$this->assertSame( [ GetItemStatementsValidator::CONTEXT_PROPERTY_ID_VALUE => 'X123' ], $e->getErrorContext() );
		}
	}

	/**
	 * @dataProvider validRequestProvider
	 * @doesNotPerformAssertions
	 */
	public function testWithValidRequest( GetItemStatementsRequest $request ): void {
		$this->newStatementsValidator()->assertValidRequest( $request );
	}

	public function validRequestProvider(): Generator {
		yield [ new GetItemStatementsRequest( 'Q321' ) ];
		yield [ new GetItemStatementsRequest( 'Q321', 'P123' ) ];
	}

	private function newStatementsValidator(): GetItemStatementsValidator {
		return ( new GetItemStatementsValidator( new ItemIdValidator() ) );
	}

}
