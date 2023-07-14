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
			$this->newStatementsValidator()->assertValidRequest( new GetPropertyStatementsRequest( 'X123' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X123', $e->getErrorMessage() );
			$this->assertSame( [ GetPropertyStatementsValidator::CONTEXT_PROPERTY_ID => 'X123' ], $e->getErrorContext() );
		}
	}

	public function testWithInvalidFilterPropertyId(): void {
		try {
			$this->newStatementsValidator()->assertValidRequest(
				new GetPropertyStatementsRequest( 'P123', 'X123' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X123', $e->getErrorMessage() );
			$this->assertSame( [ GetPropertyStatementsValidator::CONTEXT_PROPERTY_ID => 'X123' ], $e->getErrorContext() );
		}
	}

	/**
	 * @dataProvider validRequestProvider
	 * @doesNotPerformAssertions
	 */
	public function testWithValidRequest( GetPropertyStatementsRequest $request ): void {
		$this->newStatementsValidator()->assertValidRequest( $request );
	}

	public static function validRequestProvider(): Generator {
		yield [ new GetPropertyStatementsRequest( 'P321' ) ];
		yield [ new GetPropertyStatementsRequest( 'P321', 'P123' ) ];
	}

	private function newStatementsValidator(): GetPropertyStatementsValidator {
		return ( new GetPropertyStatementsValidator( new PropertyIdValidator() ) );
	}

}
