<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LoggingTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider expectedErrorProvider
	 */
	public function testGivenExpectedError_logsNothing(): void {
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->never() )->method( $this->anything() );

		$result = $this->newGraphQLService( $logger )
			->query( '{ item(id: "Q999999") { id } }' );

		$this->assertArrayHasKey( 'errors', $result );
	}

	public static function expectedErrorProvider(): Generator {
		yield 'item not found' => [ '{ item(id: "Q999999") { id } }' ];
		yield 'syntax error' => [ '{ item(id: "Q123") }' ];
	}

	public function testGivenUnexpectedError_logsError(): void {
		$error = new RuntimeException( 'unexpected error' );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( $error->getMessage(), [ 'trace' => $error->getTraceAsString() ] );

		$entityLookup = $this->createStub( EntityLookup::class );
		$entityLookup->method( 'getEntity' )->willThrowException( $error );

		$result = $this->newGraphQLService( $logger, $entityLookup )
			->query( '{ item(id: "Q666") { id } }' );

		$this->assertArrayHasKey( 'errors', $result );
	}

	private function newGraphQLService( LoggerInterface $logger, ?EntityLookup $entityLookup = null ): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup ?? $this->createStub( EntityLookup::class ) );
		$this->setService( 'WikibaseRepo.Logger', $logger );

		return WbReuse::getGraphQLService();
	}
}
