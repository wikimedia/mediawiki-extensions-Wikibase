<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyStatements;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		try {
			$this->newValidator()->assertValidRequest( new GetPropertyStatementsRequest( 'X123' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X123', $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PROPERTY_ID => 'X123' ], $e->getErrorContext() );
		}
	}

	/**
	 * @dataProvider provideInvalidPropertyId
	 */
	public function testWithInvalidFilterPropertyId( string $invalidPropertyId ): void {
		try {
			$this->newValidator()->assertValidRequest( new GetPropertyStatementsRequest( 'P123', $invalidPropertyId ) );
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
	public function testWithValidRequest( GetPropertyStatementsRequest $request ): void {
		$this->newValidator()->assertValidRequest( $request );
	}

	public static function validRequestProvider(): Generator {
		yield [ new GetPropertyStatementsRequest( 'P321' ) ];
		yield [ new GetPropertyStatementsRequest( 'P321', 'P123' ) ];
	}

	private function newValidator(): GetPropertyStatementsValidator {
		return ( new GetPropertyStatementsValidator( new PropertyIdValidator() ) );
	}

}
