<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLErrorLogger;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLErrorLogger
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLErrorLoggerTest extends TestCase {

	/**
	 * @dataProvider errorsWithNoLoggingProvider
	 */
	public function testLogsNothing( array $errors ): void {
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->never() )->method( $this->anything() );

		( new GraphQLErrorLogger( $logger ) )->logUnexpectedErrors( $errors );
	}

	public static function errorsWithNoLoggingProvider(): Generator {
		yield 'no errors' => [ [] ];

		yield 'expected error' => [
			[ new Error( 'expected', previous: GraphQLError::itemNotFound( 'Q1' ) ) ],
		];

		yield 'error with no previous exception' => [
			[ new Error( 'no previous' ) ],
		];
	}

	/**
	 * @dataProvider unexpectedErrorsProvider
	 */
	public function testLogsUnexpectedErrors( array $errors, RuntimeException $expectedLog ): void {
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( $expectedLog->getMessage(), [ 'trace' => $expectedLog->getTraceAsString() ] );

		( new GraphQLErrorLogger( $logger ) )->logUnexpectedErrors( $errors );
	}

	public static function unexpectedErrorsProvider(): Generator {
		$unexpected = new RuntimeException( 'Unexpected error' );

		yield 'single unexpected error' => [
			[ new Error( 'wrapped', previous: $unexpected ) ],
			$unexpected,
		];

		yield 'only unexpected error is logged among mixed errors' => [
			[
				new Error( 'expected', previous: GraphQLError::itemNotFound( 'Q1' ) ),
				new Error( 'unexpected', previous: $unexpected ),
				new Error( 'no previous' ),
			],
			$unexpected,
		];
	}
}
