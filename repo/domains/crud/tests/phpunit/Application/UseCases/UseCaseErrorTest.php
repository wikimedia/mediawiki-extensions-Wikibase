<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError
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

	public static function provideValidUseCaseErrorData(): Generator {
		yield 'valid error without context' => [
			UseCaseError::RESOURCE_NOT_FOUND,
			'The requested resource does not exist',
			[ UseCaseError::CONTEXT_RESOURCE_TYPE => 'aliases' ],
		];

		yield 'valid error with context' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			"Invalid path parameter: 'property_id'",
			[ UseCaseError::CONTEXT_PARAMETER => 'property_id' ],
		];
	}

	/**
	 * @dataProvider provideInvalidUseCaseErrorData
	 */
	public function testInvalidInstantiation( string $errorCode, string $errorMessage, array $errorContext = [] ): void {
		$this->expectException( LogicException::class );
		new UseCaseError( $errorCode, $errorMessage, $errorContext );
	}

	public static function provideInvalidUseCaseErrorData(): Generator {
		yield 'error code not defined' => [ 'not-a-valid-error-code', 'not a valid error code' ];

		yield 'error context contains incorrect key' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			'incorrect context key',
			[ 'incorrect-context-key' => 'potato', UseCaseError::INVALID_PATH_PARAMETER => 'property_id' ],
		];

		yield 'error context is missing expected keys' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			'error context key is missing',
		];

		yield 'wrong path context field name' => [
			UseCaseError::REFERENCED_RESOURCE_NOT_FOUND,
			'The referenced resource does not exist',
			[ UseCaseError::CONTEXT_TITLE => 'Test_article' ],
		];
	}

	/**
	 * @dataProvider permissionDeniedContextProvider
	 */
	public function testNewPermissionDenied( UseCaseError $error, array $expectedContext ): void {
		$this->assertEquals( $expectedContext, $error->getErrorContext() );
	}

	public static function permissionDeniedContextProvider(): Generator {
		yield 'no additional context' => [
			UseCaseError::newPermissionDenied( 'some-reason' ),
			[ UseCaseError::CONTEXT_DENIAL_REASON => 'some-reason' ],
		];

		yield 'with additional context' => [
			UseCaseError::newPermissionDenied( 'some-other-reason', [ 'some' => 'context' ] ),
			[
				UseCaseError::CONTEXT_DENIAL_REASON => 'some-other-reason',
				UseCaseError::CONTEXT_DENIAL_CONTEXT => [ 'some' => 'context' ],
			],
		];
	}

}
