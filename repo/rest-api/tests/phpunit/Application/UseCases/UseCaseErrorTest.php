<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\UseCaseError
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UseCaseErrorTest extends TestCase {

	/**
	 * @dataProvider provideValidUseCaseErrorData
	 */
	public function testHappyPath( string $errorCode, string $errorMessage, array $errorContext = [] ): void {
		$useCaseError = new UseCaseError( $errorCode, $errorMessage, $errorContext );

		$this->assertSame( $errorCode, $useCaseError->getErrorCode() );
		$this->assertSame( $errorMessage, $useCaseError->getErrorMessage() );
		$this->assertSame( $errorContext, $useCaseError->getErrorContext() );
	}

	public function provideValidUseCaseErrorData(): Generator {
		yield 'valid error without context' => [ UseCaseError::ALIASES_NOT_DEFINED, 'aliases not defined' ];

		yield 'valid error with context' => [
			UseCaseError::INVALID_PROPERTY_ID,
			'invalid property id',
			[ UseCaseError::CONTEXT_PROPERTY_ID => 'X123' ],
		];
	}

	/**
	 * @dataProvider provideInvalidUseCaseErrorData
	 */
	public function testInvalidInstantiation( string $errorCode, string $errorMessage, array $errorContext = [] ): void {
		$this->expectException( LogicException::class );
		new UseCaseError( $errorCode, $errorMessage, $errorContext );
	}

	public function provideInvalidUseCaseErrorData(): Generator {
		yield 'error code not defined' => [ 'not-a-valid-error-code', 'not a valid error code' ];

		yield 'error context contains incorrect key' => [
			UseCaseError::INVALID_PROPERTY_ID,
			'incorrect context key',
			[ 'incorrect-context-key' => 'potato', UseCaseError::CONTEXT_PROPERTY_ID => 'X123' ],
		];

		yield 'error context is missing expected keys' => [
			UseCaseError::INVALID_PROPERTY_ID,
			'error context key is missing',
		];
	}

}
