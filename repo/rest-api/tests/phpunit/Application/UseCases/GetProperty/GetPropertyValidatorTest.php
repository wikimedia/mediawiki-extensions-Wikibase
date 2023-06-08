<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetProperty;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyValidatorTest extends TestCase {

	/**
	 * @dataProvider dataProviderPass
	 * @doesNotPerformAssertions
	 */
	public function testValidatePass( GetPropertyRequest $request ): void {
		( new GetPropertyValidator( new PropertyIdValidator() ) )->assertValidRequest( $request );
	}

	public function dataProviderPass(): Generator {
		yield 'valid ID' => [
			new GetPropertyRequest( 'P123' ),
		];
	}

	/**
	 * @dataProvider dataProviderFail
	 */
	public function testValidateFail(
		GetPropertyRequest $request,
		string $expectedCode,
		string $expectedMessage
	): void {
		try {
			( new GetPropertyValidator( new PropertyIdValidator() ) )->assertValidRequest( $request );

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedCode, $e->getErrorCode() );
			$this->assertEquals( $expectedMessage, $e->getErrorMessage() );
		}
	}

	public function dataProviderFail(): Generator {
		yield 'invalid property ID' => [
			new GetPropertyRequest( 'X123' ),
			UseCaseError::INVALID_PROPERTY_ID,
			'Not a valid property ID: X123',
		];
	}
}
