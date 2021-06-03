<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\LoggerInterface;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.Logger',
			$this->createMock( LoggerInterface::class )
		);

		$repoDb = $this->createStub( RepoDomainDb::class );
		$dbFactory = $this->createStub( RepoDomainDbFactory::class );
		$dbFactory->method( 'newForEntityType' )
			->willReturn( $repoDb );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory', $dbFactory );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getMainWANObjectCache' );

		$this->assertInstanceOf(
			TermInLangIdsResolverFactory::class,
			$this->getService( 'WikibaseClient.TermInLangIdsResolverFactory' )
		);
	}

}
